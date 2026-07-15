<script setup lang="ts">
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import WorklogPanel from '@/components/worklog/WorklogPanel.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DailySummary, DashboardFilters, Paginated, TimeEntry, UserOption, WorklogKpis } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

interface UserSummary extends WorklogKpis {
    user_id: number;
    name: string;
}
const props = defineProps<{
    filters: DashboardFilters;
    users: UserOption[];
    selectedUser: UserOption | null;
    dailySummaries: DailySummary[];
    kpis: WorklogKpis;
    entries: Paginated<TimeEntry>;
    calendarEntries: TimeEntry[];
    userSummaries: UserSummary[];
}>();
const filters = reactive({ from: props.filters.from, to: props.filters.to, user_id: props.filters.user_id ? String(props.filters.user_id) : 'all' });
const todayString = dateString(new Date());
const exportUrl = computed(() =>
    route('admin.export', { from: filters.from, to: filters.to, user_id: filters.user_id === 'all' ? undefined : filters.user_id }),
);
const panelFilters = computed<DashboardFilters>(() => ({
    from: filters.from,
    to: filters.to,
    user_id: filters.user_id === 'all' ? null : Number(filters.user_id),
}));
function apply(): void {
    router.get(
        route('admin.reports.index'),
        { from: filters.from, to: filters.to, user_id: filters.user_id === 'all' ? undefined : filters.user_id },
        { preserveScroll: true },
    );
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
    filters.from = dateString(from);
    filters.to = dateString(to);
    apply();
}

function resetDateRange(): void {
    const today = new Date();
    filters.from = dateString(new Date(today.getFullYear(), today.getMonth(), 1));
    filters.to = dateString(today);
    apply();
}
function dateString(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
</script>

<template>
    <Head title="Riportok" />
    <AppLayout :breadcrumbs="[{ title: 'Riportok', href: '/admin/reports' }]">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold">Munkaidő-riportok</h1>
                <p class="text-sm text-muted-foreground">Csapatszintű vagy egyéni kimutatások és export.</p>
            </div>
            <Card
                ><CardContent class="grid gap-3 p-4 lg:grid-cols-[minmax(220px,1fr)_180px_180px_auto] lg:items-end"
                    ><div class="grid gap-2">
                        <Label for="report-user">Felhasználó</Label
                        ><Select v-model="filters.user_id"
                            ><SelectTrigger id="report-user"><SelectValue /></SelectTrigger
                            ><SelectContent
                                ><SelectItem value="all">Minden felhasználó</SelectItem
                                ><SelectItem v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</SelectItem></SelectContent
                            ></Select
                        >
                    </div>
                    <div class="grid gap-2">
                        <Label for="report-from">Ettől</Label><Input id="report-from" v-model="filters.from" type="date" :max="todayString" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="report-to">Eddig</Label><Input id="report-to" v-model="filters.to" type="date" :max="todayString" />
                    </div>
                    <div class="flex gap-2">
                        <Button class="flex-1 lg:flex-none" @click="apply">Szűrés</Button>
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            title="Időszak visszaállítása"
                            aria-label="Időszak visszaállítása az aktuális hónapra"
                            @click="resetDateRange"
                        >
                            <RotateCcw class="size-4" />
                        </Button></div></CardContent></Card
            ><WorklogPanel
                :filters="panelFilters"
                :daily-summaries="dailySummaries"
                :kpis="kpis"
                :entries="entries.data"
                :calendar-entries="calendarEntries"
                :export-url="exportUrl"
                :user-id="selectedUser?.id"
                :owner-name="selectedUser?.name"
                :editable="!!selectedUser"
                :creatable="!!selectedUser"
                :team-aggregate="!selectedUser"
                @preset="preset"
            />
            <PaginationLinks :links="entries.links" />
            <Card v-if="!selectedUser"
                ><CardHeader><CardTitle>Felhasználónkénti összesítés</CardTitle></CardHeader
                ><CardContent
                    ><Table
                        ><TableHeader
                            ><TableRow
                                ><TableHead>Felhasználó</TableHead><TableHead>Munkanapok</TableHead><TableHead>Teljes idő</TableHead
                                ><TableHead>Napi átlag</TableHead></TableRow
                            ></TableHeader
                        ><TableBody
                            ><TableRow v-for="summary in userSummaries" :key="summary.user_id"
                                ><TableCell class="font-medium">{{ summary.name }}</TableCell
                                ><TableCell>{{ summary.workdays }}</TableCell
                                ><TableCell>{{ summary.total_duration }}</TableCell
                                ><TableCell>{{ summary.average_duration }}</TableCell></TableRow
                            ></TableBody
                        ></Table
                    ></CardContent
                ></Card
            >
        </div>
    </AppLayout>
</template>
