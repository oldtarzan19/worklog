<script setup lang="ts">
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import WorklogPanel from '@/components/worklog/WorklogPanel.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DailySummary, DashboardFilters, Paginated, TimeEntry, WorklogKpis } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const props = defineProps<{
    filters: DashboardFilters;
    dailySummaries: DailySummary[];
    kpis: WorklogKpis;
    entries: Paginated<TimeEntry>;
    calendarEntries: TimeEntry[];
}>();
const dates = reactive({ from: props.filters.from, to: props.filters.to });
const exportUrl = computed(() => route('export.own', dates));
const todayString = localDate(new Date());

function apply(): void {
    router.get(route('dashboard'), dates, { preserveScroll: true });
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
        to = today;
    } else {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to = today;
    }
    dates.from = localDate(from);
    dates.to = localDate(to);
    apply();
}

function resetDateRange(): void {
    const today = new Date();
    dates.from = localDate(new Date(today.getFullYear(), today.getMonth(), 1));
    dates.to = localDate(today);
    apply();
}

function localDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
</script>

<template>
    <Head title="Munkaidőm" />
    <AppLayout :breadcrumbs="[{ title: 'Munkaidőm', href: '/dashboard' }]">
        <div class="flex w-full min-w-0 max-w-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Munkaidőm</h1>
                <p class="text-sm text-muted-foreground">Rögzítsd és tekintsd át a ledolgozott idődet.</p>
            </div>
            <div class="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div class="grid gap-2"><Label for="from">Ettől</Label><Input id="from" v-model="dates.from" type="date" :max="todayString" /></div>
                <div class="grid gap-2"><Label for="to">Eddig</Label><Input id="to" v-model="dates.to" type="date" :max="todayString" /></div>
                <div class="flex gap-2">
                    <Button class="flex-1 sm:flex-none" @click="apply">Szűrés</Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        title="Időszak visszaállítása"
                        aria-label="Időszak visszaállítása az aktuális hónapra"
                        @click="resetDateRange"
                    >
                        <RotateCcw class="size-4" />
                    </Button>
                </div>
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
            <PaginationLinks :links="entries.links" />
        </div>
    </AppLayout>
</template>
