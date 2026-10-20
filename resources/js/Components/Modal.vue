<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { X } from '@lucide/vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    maxWidth: { type: String, default: 'lg' },
});

const emit = defineEmits(['close']);

const widthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
}[props.maxWidth] ?? 'max-w-lg';

function onKeydown(event) {
    if (event.key === 'Escape' && props.show) emit('close');
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-4">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="emit('close')"></div>
                <div
                    class="relative max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                    :class="widthClass"
                >
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-lg font-bold tracking-tight text-slate-900">{{ title }}</h3>
                            <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
                        </div>
                        <button type="button" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup dialog" @click="emit('close')">
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
