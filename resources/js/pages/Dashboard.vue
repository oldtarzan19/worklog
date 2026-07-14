<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import WorklogPanel from '@/components/worklog/WorklogPanel.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DailySummary, DashboardFilters, SharedData, TimeEntry, WorklogKpis } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { toast } from 'vue-sonner';

interface PaginatedEntries {
    data: TimeEntry[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    filters: DashboardFilters;
    dailySummaries: DailySummary[];
    kpis: WorklogKpis;
    entries: PaginatedEntries;
    calendarEntries: TimeEntry[];
}>();
const page = usePage<SharedData>();
const dates = reactive({ from: props.filters.from, to: props.filters.to });
const exportUrl = computed(() => route('export.own', dates));

watch(
    () => page.props.flash.success,
    (message) => {
        if (message) toast.success(message);
    },
    { immediate: true },
);

function apply(): void {
    router.get(route('dashboard'), dates, { preserveState: true, preserveScroll: true });
}

function preset(type: string): void {
    const today = new Date();
    let from: Date;
    let to: Date;
    if (type === 'previous-month') {
        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        to = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (type === 'current-year') {
        from = new Date(today.getFullYear(), 0, 1);
        to = new Date(today.getFullYear(), 11, 31);
    } else {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    }
    dates.from = localDate(from);
    dates.to = localDate(to);
    apply();
}

function localDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
</script>

<template>
    <Head title="Munkaidőm" />
    <AppLayout :breadcrumbs="[{ title: 'Munkaidőm', href: '/dashboard' }]">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Munkaidőm</h1>
                <p class="text-sm text-muted-foreground">Rögzítsd és tekintsd át a ledolgozott idődet.</p>
            </div>
            <div class="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div class="grid gap-2"><Label for="from">Ettől</Label><Input id="from" v-model="dates.from" type="date" /></div>
                <div class="grid gap-2"><Label for="to">Eddig</Label><Input id="to" v-model="dates.to" type="date" /></div>
                <Button @click="apply">Szűrés</Button>
            </div>
            <WorklogPanel
                :filters="filters"
                :daily-summaries="dailySummaries"
                :kpis="kpis"
                :entries="entries.data"
                :calendar-entries="calendarEntries"
                :export-url="exportUrl"
                @preset="preset"
            />
            <div v-if="entries.links.length > 3" class="flex flex-wrap justify-center gap-1">
                <Button
                    v-for="link in entries.links"
                    :key="link.label"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    ><Link :href="link.url || '#'" preserve-scroll>{{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}</Link></Button
                >
            </div>
        </div>
    </AppLayout>
</template>
