const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const longDateFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export function formatDate(value) {
    if (!value) return '—';
    return dateFormatter.format(new Date(value));
}

export function formatLongDate(value) {
    if (!value) return '—';
    return longDateFormatter.format(new Date(value));
}

export function formatDateTime(value) {
    if (!value) return '—';
    return dateTimeFormatter.format(new Date(value));
}

export function formatDateInput(value) {
    if (!value) return '';
    const date = new Date(value);
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
}

export function formatDateTimeInput(value) {
    if (!value) return '';
    const date = new Date(value);
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

export function relativeTime(value) {
    if (!value) return '—';
    const diff = Date.now() - new Date(value).getTime();
    const minutes = Math.round(diff / 60000);
    if (Math.abs(minutes) < 1) return 'baru saja';
    if (Math.abs(minutes) < 60) return `${minutes} menit lalu`;
    const hours = Math.round(minutes / 60);
    if (Math.abs(hours) < 24) return `${hours} jam lalu`;
    const days = Math.round(hours / 24);
    if (Math.abs(days) < 30) return `${days} hari lalu`;
    return formatDate(value);
}

export function initials(name) {
    if (!name) return '?';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export function severityClass(severity) {
    return {
        1: 'bg-sky-50 text-sky-700 ring-sky-200',
        2: 'bg-amber-50 text-amber-700 ring-amber-200',
        3: 'bg-orange-50 text-orange-700 ring-orange-200',
        4: 'bg-rose-50 text-rose-700 ring-rose-200',
    }[severity] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
}

export function severityBarClass(severity) {
    return {
        1: 'bg-sky-400',
        2: 'bg-amber-400',
        3: 'bg-orange-500',
        4: 'bg-rose-500',
    }[severity] ?? 'bg-slate-300';
}

export function statusClass(status) {
    return {
        baru: 'bg-rose-50 text-rose-700 ring-rose-200',
        ditinjau: 'bg-amber-50 text-amber-700 ring-amber-200',
        ditangani: 'bg-sky-50 text-sky-700 ring-sky-200',
        menunggu_rujukan: 'bg-violet-50 text-violet-700 ring-violet-200',
        selesai: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ditinjau_ulang: 'bg-slate-100 text-slate-700 ring-slate-200',
    }[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

export function studentStatusClass(status) {
    return {
        aktif: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        calon: 'bg-sky-50 text-sky-700 ring-sky-200',
        cuti: 'bg-amber-50 text-amber-700 ring-amber-200',
        pindah: 'bg-slate-100 text-slate-700 ring-slate-200',
        mundur: 'bg-rose-50 text-rose-700 ring-rose-200',
        lulus: 'bg-violet-50 text-violet-700 ring-violet-200',
    }[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

export function attendanceClass(status) {
    return {
        hadir: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        izin: 'bg-sky-50 text-sky-700 ring-sky-200',
        sakit: 'bg-amber-50 text-amber-700 ring-amber-200',
        alpa: 'bg-rose-50 text-rose-700 ring-rose-200',
        dispensasi: 'bg-violet-50 text-violet-700 ring-violet-200',
        pulang_berizin: 'bg-slate-100 text-slate-700 ring-slate-200',
    }[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

export function attendanceDot(status) {
    return {
        hadir: 'bg-emerald-500',
        izin: 'bg-sky-500',
        sakit: 'bg-amber-500',
        alpa: 'bg-rose-500',
        dispensasi: 'bg-violet-500',
        pulang_berizin: 'bg-slate-400',
    }[status] ?? 'bg-slate-300';
}

export function confidentialityClass(level) {
    return {
        normal: 'bg-slate-100 text-slate-600 ring-slate-200',
        rahasia: 'bg-amber-50 text-amber-700 ring-amber-200',
        sangat_rahasia: 'bg-rose-50 text-rose-700 ring-rose-200',
    }[level] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

export function roleClass(role) {
    return {
        admin: 'bg-slate-800 text-white ring-slate-800',
        kepala_sekolah: 'bg-brand-700 text-white ring-brand-700',
        tppk: 'bg-rose-600 text-white ring-rose-600',
        bk: 'bg-violet-600 text-white ring-violet-600',
        wali_kelas: 'bg-sky-100 text-sky-800 ring-sky-200',
        wali_asrama: 'bg-amber-100 text-amber-800 ring-amber-200',
        guru: 'bg-slate-100 text-slate-700 ring-slate-200',
    }[role] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

export function percentage(part, total) {
    if (!total) return 0;
    return Math.round((part / total) * 100);
}
