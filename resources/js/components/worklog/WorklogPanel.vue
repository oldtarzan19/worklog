<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import type { DailySummary, DashboardFilters, TimeEntry, WorklogKpis } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { CalendarDate, type DateValue } from '@internationalized/date';
import { GroupedBar } from '@unovis/ts';
import { VisAxis, VisGroupedBar, VisTooltip, VisXYContainer } from '@unovis/vue';
import { CalendarPlus, ChevronDown, Clock3, Download, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, shallowRef, watch } from 'vue';

interface ChartDatum {
    date: string;
    hours: number;
}

const NOTE_MAX_LENGTH = 500;

const props = withDefaults(
    defineProps<{
        filters: DashboardFilters;
        dailySummaries: DailySummary[];
        kpis: WorklogKpis;
        entries: TimeEntry[];
        calendarEntries?: TimeEntry[];
        exportUrl: string;
        userId?: number | null;
        ownerName?: string | null;
        editable?: boolean;
        creatable?: boolean;
        calendarInteractive?: boolean;
        teamAggregate?: boolean;
        detailsPageSize?: number | null;
    }>(),
    { editable: true, creatable: true, calendarInteractive: true, teamAggregate: false, detailsPageSize: null },
);
defineEmits<{ preset: [value: string] }>();

const selectedDate = shallowRef<DateValue | undefined>(
    new CalendarDate(Number(props.filters.from.slice(0, 4)), Number(props.filters.from.slice(5, 7)), 1),
);
const sheetOpen = ref(false);
const newEntryOpen = ref(false);
const editEntryOpen = ref(false);
const editing = ref<TimeEntry | null>(null);
const summaryMap = computed(() => new Map(props.dailySummaries.map((summary) => [summary.date, summary])));
const chartData = computed<ChartDatum[]>(() =>
    props.dailySummaries.map((summary) => ({ date: summary.date, hours: Math.round(summary.minutes / 6) / 10 })),
);
const selectedDateString = computed(() => selectedDate.value?.toString() ?? '');
const selectedEntries = computed(() => (props.calendarEntries ?? props.entries).filter((entry) => entry.work_date === selectedDateString.value));
const visibleEntryCount = ref(props.detailsPageSize ?? props.entries.length);
const visibleEntries = computed(() => props.entries.slice(0, visibleEntryCount.value));
const hasMoreEntries = computed(() => visibleEntryCount.value < props.entries.length);
const showsUserColumn = computed(() => props.entries.some((entry) => entry.user_name));
const totalTimeLabel = computed(() => (props.teamAggregate ? 'Összes emberóra' : 'Összes munkaidő'));
const dailyAverageLabel = computed(() => (props.teamAggregate ? 'Napi átlag / fő' : 'Napi átlag'));
const calendarTitle = computed(() => (props.teamAggregate ? 'Emberóra-naptár' : 'Munkaidő-naptár'));
const chartTitle = computed(() => (props.teamAggregate ? 'Napi emberóra' : 'Napi munkaidő'));
const chartDescription = computed(() =>
    props.teamAggregate ? 'A csapat összesített emberórái napi bontásban.' : 'A szűrt időszak órái napi bontásban.',
);
const now = new Date();
const today = new CalendarDate(now.getFullYear(), now.getMonth() + 1, now.getDate());
const todayString = today.toString();

watch(
    () => [props.entries, props.detailsPageSize] as const,
    () => {
        visibleEntryCount.value = props.detailsPageSize ?? props.entries.length;
    },
);

watch(
    () => props.filters.from,
    (from) => {
        selectedDate.value = new CalendarDate(Number(from.slice(0, 4)), Number(from.slice(5, 7)), 1);
        sheetOpen.value = false;
        editing.value = null;
    },
);

const form = useForm({
    user_id: props.userId ?? undefined,
    work_date: selectedDateString.value,
    start_time: '08:00',
    end_time: '16:00',
    note: '',
});

const newEntryForm = useForm({
    user_id: props.userId ?? undefined,
    work_date: todayString,
    start_time: '08:00',
    end_time: '16:00',
    note: '',
});
const noteCharactersRemaining = computed(() => NOTE_MAX_LENGTH - form.note.length);
const newNoteCharactersRemaining = computed(() => NOTE_MAX_LENGTH - newEntryForm.note.length);

function selectDate(value: DateValue | undefined): void {
    if (!value || isFutureDate(value) || props.calendarInteractive === false) return;
    selectedDate.value = value;
    editing.value = null;
    editEntryOpen.value = false;
    sheetOpen.value = true;
}

function setSheetOpen(open: boolean): void {
    sheetOpen.value = open;

    if (!open) {
        selectedDate.value = undefined;
        editing.value = null;
        editEntryOpen.value = false;
        form.clearErrors();
    }
}

function isFutureDate(value: DateValue): boolean {
    return value.compare(today) > 0;
}

function editEntry(entry: TimeEntry): void {
    editing.value = entry;
    form.user_id = entry.user_id;
    form.work_date = entry.work_date;
    form.start_time = entry.start_time;
    form.end_time = entry.end_time;
    form.note = entry.note ?? '';
    form.clearErrors();
    editEntryOpen.value = true;
}

function setEditEntryOpen(open: boolean): void {
    editEntryOpen.value = open;

    if (!open) {
        editing.value = null;
        form.clearErrors();
    }
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editEntryOpen.value = false;
            editing.value = null;
            form.reset('start_time', 'end_time', 'note');
            form.work_date = selectedDateString.value;
        },
    };
    if (!editing.value) return;
    form.patch(route('work-entries.update', { work_entry: editing.value.id }), options);
}

function submitNewEntry(): void {
    newEntryForm.user_id = props.userId ?? undefined;
    newEntryForm.post(route('work-entries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newEntryOpen.value = false;
            newEntryForm.reset('start_time', 'end_time', 'note');
            newEntryForm.work_date = todayString;
        },
    });
}

function remove(entry: TimeEntry): void {
    router.delete(route('work-entries.destroy', { work_entry: entry.id }), { preserveScroll: true });
}

function loadMoreEntries(): void {
    visibleEntryCount.value = Math.min(visibleEntryCount.value + (props.detailsPageSize ?? props.entries.length), props.entries.length);
}

function heatClass(day: DateValue): string {
    if (isFutureDate(day)) return 'bg-muted/50 text-muted-foreground opacity-35';
    const minutes = summaryMap.value.get(day.toString())?.minutes ?? 0;
    if (minutes >= 480) return 'bg-emerald-500/35';
    if (minutes >= 360) return 'bg-emerald-500/25';
    if (minutes > 0) return 'bg-emerald-500/15';
    return '';
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="grid flex-1 grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                <Button variant="outline" size="sm" @click="$emit('preset', 'current-month')">Aktuális hónap</Button>
                <Button variant="outline" size="sm" @click="$emit('preset', 'previous-month')">Előző hónap</Button>
                <Button variant="outline" size="sm" @click="$emit('preset', 'current-year')">Aktuális év</Button>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <Dialog v-if="creatable !== false" v-model:open="newEntryOpen">
                    <DialogTrigger as-child>
                        <Button size="lg" class="shadow-sm"><CalendarPlus class="mr-2 size-5" />Munkaidő rögzítése</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Új munkaidő rögzítése</DialogTitle>
                            <DialogDescription>Válaszd ki a munkavégzés napját és add meg az idősávot.</DialogDescription>
                        </DialogHeader>
                        <form class="grid gap-5" @submit.prevent="submitNewEntry">
                            <div class="grid gap-2">
                                <Label for="new-work-date">Munkavégzés napja</Label>
                                <Input id="new-work-date" v-model="newEntryForm.work_date" type="date" :max="todayString" required />
                                <InputError :message="newEntryForm.errors.work_date" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-2">
                                    <Label for="new-start">Kezdés</Label>
                                    <Input id="new-start" v-model="newEntryForm.start_time" type="time" required />
                                    <InputError :message="newEntryForm.errors.start_time" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="new-end">Befejezés</Label>
                                    <Input id="new-end" v-model="newEntryForm.end_time" type="time" required />
                                    <InputError :message="newEntryForm.errors.end_time" />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="new-note">Megjegyzés</Label>
                                <Textarea
                                    id="new-note"
                                    v-model="newEntryForm.note"
                                    placeholder="Opcionális megjegyzés"
                                    :maxlength="NOTE_MAX_LENGTH"
                                    aria-describedby="new-note-counter"
                                />
                                <p
                                    id="new-note-counter"
                                    :class="[
                                        'text-right text-xs tabular-nums',
                                        newNoteCharactersRemaining <= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-muted-foreground',
                                    ]"
                                    aria-live="polite"
                                >
                                    {{ newNoteCharactersRemaining }} karakter maradt
                                </p>
                                <InputError :message="newEntryForm.errors.note" />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" @click="newEntryOpen = false">Mégse</Button>
                                <Button type="submit" :disabled="newEntryForm.processing">Munkaidő mentése</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
                <Button as-child variant="outline">
                    <a :href="exportUrl"><Download class="mr-2 size-4" />XLSX export</a>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2"
                    ><CardDescription>{{ totalTimeLabel }}</CardDescription
                    ><CardTitle class="text-3xl">{{ kpis.total_duration }}</CardTitle></CardHeader
                >
            </Card>
            <Card>
                <CardHeader class="pb-2"
                    ><CardDescription>Munkanapok</CardDescription><CardTitle class="text-3xl">{{ kpis.workdays }}</CardTitle></CardHeader
                >
            </Card>
            <Card>
                <CardHeader class="pb-2"
                    ><CardDescription>{{ dailyAverageLabel }}</CardDescription
                    ><CardTitle class="text-3xl">{{ kpis.average_duration }}</CardTitle></CardHeader
                >
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,.8fr)]">
            <Card class="overflow-hidden">
                <CardHeader
                    ><CardTitle>{{ calendarTitle }}</CardTitle
                    ><CardDescription v-if="calendarInteractive !== false"
                        >A múltbeli napokra kattintva megtekintheted{{ editable !== false ? ' és szerkesztheted' : '' }} a
                        bejegyzéseket.</CardDescription
                    ><CardDescription v-else>A kiválasztott időszak csapatszintű napi összesítése.</CardDescription></CardHeader
                >
                <CardContent>
                    <Calendar
                        v-model="selectedDate"
                        locale="hu-HU"
                        class="w-full rounded-md border p-2 sm:p-3"
                        :readonly="calendarInteractive === false"
                        :is-date-disabled="isFutureDate"
                        @update:model-value="selectDate"
                    >
                        <template #day="{ day }">
                            <div
                                :class="['flex h-12 w-full min-w-0 flex-col items-center justify-center rounded-md text-xs sm:h-14', heatClass(day)]"
                            >
                                <span class="font-medium">{{ day.day }}</span>
                                <span class="text-[10px] text-muted-foreground">{{ summaryMap.get(day.toString())?.duration ?? '—' }}</span>
                            </div>
                        </template>
                    </Calendar>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    ><CardTitle>{{ chartTitle }}</CardTitle
                    ><CardDescription>{{ chartDescription }}</CardDescription></CardHeader
                >
                <CardContent>
                    <div v-if="chartData.length" class="h-[330px] w-full [--vis-primary-color:hsl(var(--primary))]">
                        <VisXYContainer :data="chartData" :margin="{ left: 40, right: 10, top: 10, bottom: 35 }">
                            <VisGroupedBar
                                :x="(_d: ChartDatum, i: number) => i"
                                :y="[(d: ChartDatum) => d.hours]"
                                color="hsl(var(--primary))"
                                :rounded-corners="4"
                            />
                            <VisAxis type="x" :x="(_d: ChartDatum, i: number) => i" :tick-format="(i: number) => chartData[i]?.date.slice(5) ?? ''" />
                            <VisAxis type="y" :tick-format="(value: number) => `${value} ${teamAggregate ? 'eó' : 'ó'}`" />
                            <VisTooltip
                                :triggers="{
                                    [GroupedBar.selectors.bar]: (d: ChartDatum) => `${d.date}: ${d.hours} ${teamAggregate ? 'emberóra' : 'óra'}`,
                                }"
                            />
                        </VisXYContainer>
                    </div>
                    <div v-else class="flex h-[330px] items-center justify-center text-sm text-muted-foreground">
                        Nincs adat a kiválasztott időszakban.
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader><CardTitle>Részletes bejegyzések</CardTitle></CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader
                            ><TableRow
                                ><TableHead v-if="showsUserColumn">Felhasználó</TableHead><TableHead>Dátum</TableHead><TableHead>Kezdés</TableHead
                                ><TableHead>Befejezés</TableHead><TableHead>Időtartam</TableHead><TableHead>Megjegyzés</TableHead></TableRow
                            ></TableHeader
                        >
                        <TableBody>
                            <TableRow v-for="entry in visibleEntries" :key="entry.id"
                                ><TableCell v-if="showsUserColumn">{{ entry.user_name }}</TableCell
                                ><TableCell>{{ entry.work_date }}</TableCell
                                ><TableCell>{{ entry.start_time }}</TableCell
                                ><TableCell>{{ entry.end_time }}</TableCell
                                ><TableCell class="font-medium">{{ entry.duration }}</TableCell
                                ><TableCell class="max-w-xs truncate">{{ entry.note || '—' }}</TableCell></TableRow
                            >
                            <TableRow v-if="!entries.length"
                                ><TableCell :colspan="showsUserColumn ? 6 : 5" class="h-24 text-center text-muted-foreground"
                                    >Nincs megjeleníthető bejegyzés.</TableCell
                                ></TableRow
                            >
                        </TableBody>
                    </Table>
                </div>
                <div v-if="hasMoreEntries" class="flex justify-center border-t p-4">
                    <Button variant="outline" @click="loadMoreEntries">
                        <ChevronDown class="mr-2 size-4" />További bejegyzések betöltése
                        <span class="ml-2 text-xs text-muted-foreground">({{ entries.length - visibleEntryCount }} maradt)</span>
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Sheet :open="sheetOpen" @update:open="setSheetOpen">
            <SheetContent class="w-full overflow-y-auto sm:max-w-lg">
                <SheetHeader
                    ><SheetTitle>{{ ownerName ? `${ownerName} – ${selectedDateString} munkaideje` : `${selectedDateString} munkaideje` }}</SheetTitle
                    ><SheetDescription>Itt az adott nap meglévő idősávjait szerkesztheted vagy törölheted.</SheetDescription></SheetHeader
                >
                <div class="flex flex-col gap-6 py-6">
                    <div class="flex flex-col gap-2">
                        <div v-if="!selectedEntries.length" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                            Ezen a napon nincs rögzített munkaidő.
                        </div>
                        <div v-for="entry in selectedEntries" :key="entry.id" class="flex items-start justify-between gap-3 rounded-lg border p-3">
                            <div class="min-w-0">
                                <p v-if="entry.user_name" class="mb-1 truncate text-sm font-semibold text-primary">{{ entry.user_name }}</p>
                                <div class="flex items-center gap-2 font-medium">
                                    <Clock3 class="size-4" />{{ entry.start_time }}–{{ entry.end_time }} · {{ entry.duration }}
                                </div>
                                <p class="text-sm text-muted-foreground">{{ entry.note || 'Nincs megjegyzés' }}</p>
                            </div>
                            <div v-if="editable !== false" class="flex gap-1">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    :aria-label="`${entry.work_date} bejegyzés szerkesztése`"
                                    @click="editEntry(entry)"
                                    ><Pencil class="size-4" /></Button
                                ><AlertDialog
                                    ><AlertDialogTrigger as-child
                                        ><Button size="icon" variant="ghost" :aria-label="`${entry.work_date} bejegyzés törlése`"
                                            ><Trash2 class="size-4 text-destructive" /></Button></AlertDialogTrigger
                                    ><AlertDialogContent
                                        ><AlertDialogHeader
                                            ><AlertDialogTitle>Biztosan törlöd?</AlertDialogTitle
                                            ><AlertDialogDescription>A művelet nem vonható vissza.</AlertDialogDescription></AlertDialogHeader
                                        ><AlertDialogFooter
                                            ><AlertDialogCancel>Mégse</AlertDialogCancel
                                            ><AlertDialogAction @click="remove(entry)">Törlés</AlertDialogAction></AlertDialogFooter
                                        ></AlertDialogContent
                                    ></AlertDialog
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <Dialog :open="editEntryOpen" @update:open="setEditEntryOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Munkaidő szerkesztése</DialogTitle>
                    <DialogDescription>
                        <template v-if="editing?.user_name">{{ editing.user_name }} · </template>{{ editing?.work_date }}
                    </DialogDescription>
                </DialogHeader>
                <form v-if="editing" class="grid gap-5" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="edit-start">Kezdés</Label>
                            <Input id="edit-start" v-model="form.start_time" type="time" required />
                            <InputError :message="form.errors.start_time" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-end">Befejezés</Label>
                            <Input id="edit-end" v-model="form.end_time" type="time" required />
                            <InputError :message="form.errors.end_time" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-note">Megjegyzés</Label>
                        <Textarea
                            id="edit-note"
                            v-model="form.note"
                            placeholder="Opcionális megjegyzés"
                            :maxlength="NOTE_MAX_LENGTH"
                            aria-describedby="edit-note-counter"
                        />
                        <p
                            id="edit-note-counter"
                            :class="[
                                'text-right text-xs tabular-nums',
                                noteCharactersRemaining <= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-muted-foreground',
                            ]"
                            aria-live="polite"
                        >
                            {{ noteCharactersRemaining }} karakter maradt
                        </p>
                        <InputError :message="form.errors.note" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="setEditEntryOpen(false)">Mégse</Button>
                        <Button type="submit" :disabled="form.processing">Módosítás mentése</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
