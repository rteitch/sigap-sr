<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    size: { type: String, default: 'md' },
    showText: { type: Boolean, default: true },
});

const page = usePage();
const branding = computed(() => page.props.branding || {});
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <span
            class="grid shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 text-white shadow-md shadow-brand-900/15"
            :class="size === 'lg' ? 'h-14 w-14' : 'h-10 w-10'"
        >
            <img
                :src="branding.logo_url || '/icon.svg'"
                :alt="'Logo ' + (branding.school_name || branding.app_name || 'SIGAP-SR')"
                class="h-full w-full object-contain"
                :class="branding.logo_url ? 'bg-white p-0.5' : ''"
            />
        </span>
        <div v-if="showText" class="min-w-0 flex-1 leading-tight">
            <p
                class="truncate font-bold tracking-tight text-slate-900"
                :class="size === 'lg' ? 'text-lg' : 'text-base'"
            >
                {{ branding.app_name || 'SIGAP-SR' }}
            </p>
            <p
                class="truncate font-medium text-slate-500"
                :class="size === 'lg' ? 'text-xs' : 'text-[11px]'"
            >
                {{ branding.tagline || 'Pengasuhan & Pembinaan' }}
            </p>
        </div>
    </div>
</template>
