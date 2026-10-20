<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarDays,
    Camera,
    Contact,
    HeartHandshake,
    MapPin,
    Plus,
    Save,
    ShieldCheck,
    Trash2,
    Upload,
    UserRound,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { formatDate } from '../../lib/labels';

const props = defineProps({
    student: { type: Object, default: null },
    classrooms: { type: Array, default: () => [] },
    dorms: { type: Array, default: () => [] },
    suggestedCode: { type: String, default: '' },
    editableStatuses: { type: Array, default: () => ['calon', 'aktif', 'cuti', 'lulus'] },
});

const page = usePage();
const options = computed(() => page.props.options);
const isEdit = computed(() => Boolean(props.student));
const statusLocked = computed(() => isEdit.value && !props.editableStatuses.includes(props.student.status));

const form = useForm({
    code: props.student?.code ?? props.suggestedCode ?? '',
    full_name: props.student?.full_name ?? '',
    nickname: props.student?.nickname ?? '',
    gender: props.student?.gender ?? 'L',
    birth_date: props.student?.birth_date ?? '',
    birth_place: props.student?.birth_place ?? '',
    entry_date: props.student?.entry_date ?? new Date().toISOString().slice(0, 10),
    status: props.student?.status ?? 'aktif',
    classroom_id: props.student?.classroom_id ?? '',
    dorm_id: props.student?.dorm_id ?? '',
    address: props.student?.address ?? '',
    identity_notes: props.student?.identity_notes ?? '',
    special_needs: props.student?.special_needs ?? '',
    socioeconomic_desil: props.student?.socioeconomic_desil ?? '',
    consent_given_at: props.student?.consent_given_at ?? '',
    consent_scope: props.student?.consent_scope ?? 'Pemrosesan data pengasuhan, presensi, dan evaluasi.',
    consent_document: props.student?.consent_document ?? '',
    consent_withdrawn_at: props.student?.consent_withdrawn_at ?? '',
    photo: null,
    remove_photo: false,
    contacts: props.student?.contacts?.length
        ? props.student.contacts.map((contact, index) => ({
              name: contact.name ?? '',
              relation: contact.relation ?? '',
              phone: contact.phone ?? '',
              is_primary: Boolean(contact.is_primary) || index === 0,
              can_pickup: Boolean(contact.can_pickup),
          }))
        : [{ name: '', relation: 'Ibu', phone: '', is_primary: true, can_pickup: true }],
});

const fileInput = ref(null);
const previewUrl = ref(props.student?.photo_url || null);

function onPhotoSelected(e) {
    const file = e.target.files?.[0];
    if (file) {
        form.photo = file;
        form.remove_photo = false;
        previewUrl.value = URL.createObjectURL(file);
    }
}

function removePhoto() {
    form.photo = null;
    form.remove_photo = true;
    previewUrl.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

const computedAge = computed(() => {
    if (!form.birth_date) return null;
    const birth = new Date(form.birth_date);
    if (isNaN(birth.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age >= 0 ? age : null;
});

const showSensitive = ref(Boolean(form.special_needs || form.socioeconomic_desil));

const filteredDorms = computed(() => {
    if (!form.gender) return props.dorms;
    return props.dorms.filter((dorm) => dorm.gender === form.gender);
});

const selectedClassroom = computed(() =>
    props.classrooms.find((c) => c.id === form.classroom_id)
);

const selectedDorm = computed(() =>
    props.dorms.find((d) => d.id === form.dorm_id)
);

function addContact() {
    form.contacts.push({ name: '', relation: '', phone: '', is_primary: form.contacts.length === 0, can_pickup: false });
}

function removeContact(index) {
    const wasPrimary = form.contacts[index]?.is_primary;
    form.contacts.splice(index, 1);
    if (wasPrimary && form.contacts.length) {
        form.contacts[0].is_primary = true;
    }
}

function setPrimary(index) {
    form.contacts.forEach((contact, contactIndex) => {
        contact.is_primary = contactIndex === index;
    });
}

function submit() {
    if (isEdit.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/siswa/${props.student.id}`);
    } else {
        form.post('/siswa');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Ubah data siswa' : 'Tambah siswa'" />

    <AppLayout>
        <PageHeader
            :title="isEdit ? 'Ubah data siswa' : 'Tambah siswa'"
            description="Isi data minimal yang diperlukan. Identitas disimpan terpisah dari data analisis dan hanya diakses staf berwenang."
            eyebrow="Data induk"
        >
            <template #actions>
                <Link :href="isEdit ? `/siswa/${student.id}` : '/siswa'" class="btn-secondary">Batal</Link>
                <button type="button" class="btn-primary" :disabled="form.processing" @click="submit">
                    <Save class="h-4 w-4" /> {{ form.processing ? 'Menyimpan…' : 'Simpan data' }}
                </button>
            </template>
        </PageHeader>

        <form class="grid gap-5 xl:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-5 xl:col-span-2">
                <section class="card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-700"><UserRound class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Identitas siswa</p>
                            <p class="text-[11px] text-slate-500">Nama dan identitas hanya untuk keperluan pengasuhan.</p>
                        </div>
                    </div>

                    <!-- Foto Avatar Upload Card -->
                    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:flex-row sm:items-center">
                        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-2xl border-2 border-white bg-slate-200 shadow-sm ring-1 ring-slate-200">
                            <img v-if="previewUrl" :src="previewUrl" alt="Foto identitas siswa" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full flex-col items-center justify-center text-slate-400">
                                <Camera class="h-6 w-6" />
                                <span class="mt-1 text-[9px] font-semibold uppercase">Foto</span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800">Foto Identitas &amp; Avatar Siswa</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">Membantu pengasuh asrama dan guru mengenali santri secara akurat. Format JPG, PNG, atau WebP maks 2MB.</p>
                            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                <input id="photo-upload" ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="onPhotoSelected" />
                                <label for="photo-upload" class="btn-secondary !px-3 !py-1.5 !text-xs cursor-pointer">
                                    <Upload class="h-3.5 w-3.5" /> {{ previewUrl ? 'Ganti foto' : 'Pilih foto' }}
                                </label>
                                <button v-if="previewUrl" type="button" class="btn-ghost !px-3 !py-1.5 !text-xs text-rose-600 hover:bg-rose-50 hover:text-rose-700" @click="removePhoto">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus
                                </button>
                            </div>
                            <p v-if="form.errors.photo" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.photo }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="full_name">Nama lengkap</label>
                            <input id="full_name" v-model="form.full_name" type="text" class="field" placeholder="Nama sesuai dokumen" required />
                            <p v-if="form.errors.full_name" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.full_name }}</p>
                        </div>
                        <div>
                            <label class="label" for="nickname">Nama panggilan</label>
                            <input id="nickname" v-model="form.nickname" type="text" class="field" placeholder="Nama yang disukai anak" />
                        </div>
                        <div>
                            <label class="label" for="code">Kode siswa (pseudonim)</label>
                            <input id="code" v-model="form.code" type="text" class="field font-mono" placeholder="SR-2026-0001" />
                            <p class="mt-1 text-[11px] text-slate-400">Dipakai pada data analisis. Kosongkan untuk dibuat otomatis.</p>
                            <p v-if="form.errors.code" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.code }}</p>
                        </div>
                        <div>
                            <label class="label" for="gender">Jenis kelamin</label>
                            <select id="gender" v-model="form.gender" class="field">
                                <option v-for="(label, value) in options.genders" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="label" for="birth_date">Tanggal lahir</label>
                                <span v-if="computedAge !== null" class="mb-1 rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">
                                    {{ computedAge }} tahun
                                </span>
                            </div>
                            <input id="birth_date" v-model="form.birth_date" type="date" class="field" />
                            <p v-if="form.errors.birth_date" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.birth_date }}</p>
                        </div>
                        <div>
                            <label class="label" for="birth_place">Tempat lahir</label>
                            <input id="birth_place" v-model="form.birth_place" type="text" class="field" placeholder="Kota/kabupaten" />
                            <p v-if="form.errors.birth_place" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.birth_place }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="address">Alamat asal</label>
                            <textarea id="address" v-model="form.address" rows="2" class="field" placeholder="Alamat keluarga atau wali"></textarea>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-50 text-sky-700"><MapPin class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Penempatan dan status</p>
                            <p class="text-[11px] text-slate-500">Kelas, asrama, dan status siklus hidup siswa.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="classroom_id">Kelas</label>
                            <select id="classroom_id" v-model="form.classroom_id" class="field">
                                <option value="">Belum ditempatkan</option>
                                <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                                    {{ classroom.name }} ({{ classroom.level }}){{ classroom.homeroom_teacher ? ' — Wali: ' + classroom.homeroom_teacher : '' }}
                                </option>
                            </select>
                            <p v-if="selectedClassroom?.homeroom_teacher" class="mt-1 text-[11px] text-brand-700">
                                Wali kelas: <span class="font-semibold">{{ selectedClassroom.homeroom_teacher }}</span>
                            </p>
                        </div>
                        <div>
                            <label class="label" for="dorm_id">Asrama</label>
                            <select id="dorm_id" v-model="form.dorm_id" class="field">
                                <option value="">Tanpa asrama</option>
                                <optgroup v-if="filteredDorms.length" :label="'Asrama ' + (options.genders[form.gender] || 'Siswa')">
                                    <option v-for="dorm in filteredDorms" :key="dorm.id" :value="dorm.id">
                                        {{ dorm.name }}{{ dorm.wali_name ? ' — Pembina: ' + dorm.wali_name : '' }}
                                    </option>
                                </optgroup>
                                <optgroup v-if="dorms.length > filteredDorms.length" label="Asrama Lainnya">
                                    <option v-for="dorm in dorms.filter(d => d.gender !== form.gender)" :key="dorm.id" :value="dorm.id">
                                        {{ dorm.name }} ({{ options.genders[dorm.gender] }})
                                    </option>
                                </optgroup>
                            </select>
                            <p v-if="selectedDorm?.wali_name" class="mt-1 text-[11px] text-brand-700">
                                Pembina asrama: <span class="font-semibold">{{ selectedDorm.wali_name }}</span>
                            </p>
                            <p v-else-if="form.gender" class="mt-1 text-[11px] text-slate-400">
                                Menampilkan asrama khusus {{ options.genders[form.gender] }}.
                            </p>
                        </div>
                        <div>
                            <label class="label" for="entry_date">Tanggal masuk</label>
                            <input id="entry_date" v-model="form.entry_date" type="date" class="field" />
                        </div>
                        <div>
                            <label class="label" for="status">Status</label>
                            <select v-if="!statusLocked" id="status" v-model="form.status" class="field">
                                <option v-for="status in editableStatuses" :key="status" :value="status">{{ options.student_statuses[status] }}</option>
                            </select>
                            <input v-else type="text" class="field bg-slate-50" :value="options.student_statuses[student.status]" readonly />
                            <p v-if="statusLocked" class="mt-1.5 text-[11px] font-medium text-amber-700">Status pindah/mundur hanya dapat diubah melalui wizard keluar (M10).</p>
                            <p v-if="form.errors.status" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <button type="button" class="flex w-full items-center justify-between text-left" @click="showSensitive = !showSensitive">
                            <span class="flex items-center gap-2 text-xs font-semibold text-slate-600"><AlertTriangle class="h-4 w-4 text-amber-500" /> Kebutuhan khusus dan data sensitif</span>
                            <span class="text-[11px] font-semibold text-brand-700">{{ showSensitive ? 'Sembunyikan' : 'Tampilkan' }}</span>
                        </button>
                        <div v-if="showSensitive" class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="label" for="special_needs">Kebutuhan khusus / akomodasi</label>
                                <textarea id="special_needs" v-model="form.special_needs" rows="2" class="field" placeholder="Contoh: pendampingan membaca, akses kursi roda."></textarea>
                                <p class="mt-1 text-[11px] text-slate-400">Akses terbatas. Jangan menulis label atau diagnosis tanpa dasar.</p>
                            </div>
                            <div>
                                <label class="label" for="desil">Desil sosial-ekonomi (opsional)</label>
                                <input id="desil" v-model="form.socioeconomic_desil" type="number" min="1" max="10" class="field" placeholder="1–10" />
                                <p class="mt-1 text-[11px] text-slate-400">Hanya jika benar-benar dibutuhkan dan hanya untuk kepala sekolah.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-700"><HeartHandshake class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Kontak keluarga dan penjemput</p>
                            <p class="text-[11px] text-slate-500">Sertakan orang yang berhak menjemput sesuai kesepakatan keluarga.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(contact, index) in form.contacts" :key="index" class="rounded-xl border border-slate-200 p-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Kontak {{ index + 1 }}</p>
                                <button v-if="form.contacts.length > 1" type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Hapus kontak" aria-label="Hapus kontak" @click="removeContact(index)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="mt-2 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="label">Nama</label>
                                    <input v-model="contact.name" type="text" class="field" placeholder="Nama wali" />
                                </div>
                                <div>
                                    <label class="label">Hubungan</label>
                                    <input v-model="contact.relation" type="text" class="field" placeholder="Ibu/Ayah/Wali" />
                                </div>
                                <div>
                                    <label class="label">Telepon</label>
                                    <input v-model="contact.phone" type="tel" class="field" placeholder="08xx" />
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" :checked="contact.is_primary" class="h-4 w-4 text-brand-700 focus:ring-brand-500" @change="setPrimary(index)" />
                                    Kontak utama
                                </label>
                                <label class="inline-flex items-center gap-2">
                                    <input v-model="contact.can_pickup" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
                                    Berhak menjemput
                                </label>
                            </div>
                            <p v-if="form.errors[`contacts.${index}.name`]" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors[`contacts.${index}.name`] }}</p>
                        </div>
                    </div>

                    <button type="button" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 hover:underline" @click="addContact">
                        <Plus class="h-3.5 w-3.5" /> Tambah kontak
                    </button>
                </section>

                <section class="card p-5">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><ShieldCheck class="h-4 w-4" /></span>
                        <div>
                            <p class="text-sm font-bold text-slate-800">Persetujuan orang tua atau wali</p>
                            <p class="text-[11px] text-slate-500">Catat tanggal, ruang lingkup, dokumen, dan penarikan persetujuan.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="consent_given">Tanggal persetujuan</label>
                            <input id="consent_given" v-model="form.consent_given_at" type="date" class="field" />
                        </div>
                        <div>
                            <label class="label" for="consent_withdrawn">Tanggal penarikan (jika ada)</label>
                            <input id="consent_withdrawn" v-model="form.consent_withdrawn_at" type="date" class="field" />
                        </div>
                        <div>
                            <label class="label" for="consent_scope">Ruang lingkup</label>
                            <input id="consent_scope" v-model="form.consent_scope" type="text" class="field" />
                        </div>
                        <div>
                            <label class="label" for="consent_document">Dokumen</label>
                            <input id="consent_document" v-model="form.consent_document" type="text" class="field" placeholder="Nama/berkas formulir" />
                        </div>
                    </div>
                    <div v-if="form.consent_withdrawn_at" class="mt-3 flex gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-800">
                        <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                        Persetujuan telah ditarik. Tinjau dasar pemrosesan data dan batasi penggunaan data siswa ini.
                    </div>
                </section>
            </div>

            <div class="space-y-5">
                <div class="card p-5">
                    <p class="text-sm font-bold text-slate-800">Ringkasan</p>
                    <dl class="mt-4 space-y-3 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Kode</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ form.code || 'Otomatis' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Kelas</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ classrooms.find((c) => c.id === form.classroom_id)?.name ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Asrama</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ dorms.find((d) => d.id === form.dorm_id)?.name ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Status</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ options.student_statuses[form.status] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-slate-500">Kontak</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ form.contacts.filter((c) => c.name).length }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                            <Save class="h-4 w-4" /> {{ form.processing ? 'Menyimpan…' : 'Simpan data' }}
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-brand-50/60 p-4">
                    <p class="flex items-center gap-2 text-xs font-bold text-brand-800"><ShieldCheck class="h-4 w-4" /> Pengingat privasi</p>
                    <ul class="mt-2 space-y-1.5 text-[11px] leading-relaxed text-brand-900/70">
                        <li>Jangan menulis label seperti "nakal" atau "bandel".</li>
                        <li>Data anak tidak boleh dibagikan melalui grup chat.</li>
                        <li>Setiap perubahan tercatat di jejak audit.</li>
                    </ul>
                </div>

                <div v-if="isEdit" class="card p-4 text-[11px] leading-relaxed text-slate-500">
                    <p class="flex items-center gap-1.5 font-semibold text-slate-700"><CalendarDays class="h-3.5 w-3.5" /> Terakhir diperbarui</p>
                    <p class="mt-1">{{ formatDate(new Date()) }} · oleh {{ page.props.auth.user.name }}</p>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
