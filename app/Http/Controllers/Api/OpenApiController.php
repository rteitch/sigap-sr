<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $base = rtrim((string) config('app.url', 'http://localhost'), '/');
        $schoolCollection = ['$ref' => '#/components/schemas/SchoolScopedCollection'];

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'SIGAP-SR API',
                'version' => (string) config('sigap.app_version', '1.0.0'),
                'description' => 'API data master sekolah untuk integrasi SIGAP-SR. Kunci API wajib dikirim melalui header X-API-Key atau Authorization: Bearer.',
                'license' => [
                    'name' => (string) config('sigap.license', 'MIT'),
                ],
                'contact' => [
                    'name' => config('sigap.developer.name'),
                    'url' => config('sigap.developer.repository'),
                ],
            ],
            'servers' => [
                ['url' => $base, 'description' => 'Server utama'],
            ],
            'tags' => [
                ['name' => 'Metadata', 'description' => 'Informasi aplikasi dan dokumentasi.'],
                ['name' => 'Master Data', 'description' => 'Data referensi sekolah. Memerlukan kunci API.'],
                ['name' => 'Health', 'description' => 'Pemeriksaan kesehatan layanan.'],
            ],
            'paths' => [
                '/api/v1/meta' => [
                    'get' => [
                        'tags' => ['Metadata'],
                        'summary' => 'Metadata aplikasi',
                        'description' => 'Informasi publik mengenai aplikasi, versi, sekolah, dan tautan dokumentasi.',
                        'responses' => $this->responses(['Metadata']),
                    ],
                ],
                '/api/v1/academic-years' => $this->masterPath('Tahun ajaran', $schoolCollection),
                '/api/v1/levels' => $this->masterPath('Jenjang', $schoolCollection),
                '/api/v1/classrooms' => $this->masterPath('Kelas aktif', $schoolCollection),
                '/api/v1/dorms' => $this->masterPath('Asrama aktif', $schoolCollection),
                '/api/v1/incident-categories' => $this->masterPath('Kategori insiden aktif', $schoolCollection),
                '/api/v1/incident-locations' => $this->masterPath('Lokasi insiden aktif', $schoolCollection),
                '/health/live' => [
                    'get' => [
                        'tags' => ['Health'],
                        'summary' => 'Liveness probe',
                        'description' => 'Menunjukkan proses aplikasi berjalan. Tidak memeriksa database.',
                        'responses' => [
                            '200' => ['description' => 'Proses hidup'],
                        ],
                    ],
                ],
                '/health/ready' => [
                    'get' => [
                        'tags' => ['Health'],
                        'summary' => 'Readiness probe',
                        'description' => 'Memeriksa database, cache, dan storage. Mengembalikan 503 bila salah satu gagal.',
                        'responses' => [
                            '200' => ['description' => 'Siap menerima trafik'],
                            '503' => ['description' => 'Belum siap'],
                        ],
                    ],
                ],
                '/health/heartbeat' => [
                    'get' => [
                        'tags' => ['Health'],
                        'summary' => 'Heartbeat scheduler',
                        'description' => 'Memeriksa apakah scheduler menjalankan sigap:heartbeat sesuai TTL.',
                        'responses' => [
                            '200' => ['description' => 'Heartbeat segar'],
                            '503' => ['description' => 'Heartbeat kedaluwarsa atau scheduler tidak berjalan'],
                        ],
                    ],
                ],
                '/health/metrics' => [
                    'get' => [
                        'tags' => ['Health'],
                        'summary' => 'Metrics Prometheus',
                        'description' => 'Memerlukan Bearer token SIGAP_METRICS_TOKEN.',
                        'security' => [['MetricsToken' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'Metrik format Prometheus',
                                'content' => ['text/plain' => ['schema' => ['type' => 'string']]],
                            ],
                            '403' => ['description' => 'Token tidak valid'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                    ],
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                    'MetricsToken' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
                'schemas' => [
                    'SchoolScopedCollection' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'count' => ['type' => 'integer'],
                                    'generated_at' => ['type' => 'string', 'format' => 'date-time'],
                                ],
                            ],
                        ],
                    ],
                    'Metadata' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'object'],
                            'links' => ['type' => 'object'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
        ], 200, ['Cache-Control' => 'public, max-age=300']);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function masterPath(string $summary, array $schema): array
    {
        return [
            'get' => [
                'tags' => ['Master Data'],
                'summary' => $summary,
                'security' => [['ApiKeyAuth' => []], ['BearerAuth' => []]],
                'responses' => [
                    '200' => [
                        'description' => 'Daftar data',
                        'content' => [
                            'application/json' => [
                                'schema' => $schema,
                            ],
                        ],
                    ],
                    '401' => ['description' => 'Kunci API tidak valid'],
                    '429' => ['description' => 'Terlalu banyak permintaan'],
                    '503' => ['description' => 'API belum dikonfigurasi'],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $tags
     * @return array<string, mixed>
     */
    private function responses(array $tags): array
    {
        return [
            '200' => [
                'description' => 'Metadata aplikasi',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Metadata'],
                    ],
                ],
            ],
        ];
    }
}
