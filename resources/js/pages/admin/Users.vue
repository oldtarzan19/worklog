<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginated, User } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Ban, CircleCheck, UserRoundCog } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

interface UserEditorForm extends Record<string, string | boolean> {
    name: string;
    email: string;
    role: 'admin' | 'user';
    is_active: boolean;
}

const props = defineProps<{ users: Paginated<User>; filters: { search?: string; role?: string; status?: string } }>();
const filters = reactive({ search: props.filters.search ?? '', role: props.filters.role ?? 'all', status: props.filters.status ?? 'all' });
const selectedUser = ref<User | null>(null);
const editorOpen = ref(false);
const disableConfirmationOpen = ref(false);
const form = useForm<UserEditorForm>({ name: '', email: '', role: 'user', is_active: true });

function apply(): void {
    router.get(
        route('admin.users.index'),
        {
            search: filters.search || undefined,
            role: filters.role === 'all' ? undefined : filters.role,
            status: filters.status === 'all' ? undefined : filters.status,
        },
        { preserveState: true },
    );
}

function openEditor(user: User): void {
    selectedUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.is_active = user.is_active;
    form.clearErrors();
    editorOpen.value = true;
}

function setEditorOpen(open: boolean): void {
    editorOpen.value = open;

    if (!open) {
        selectedUser.value = null;
        disableConfirmationOpen.value = false;
        form.clearErrors();
    }
}

function setFormStatus(value: unknown): void {
    form.is_active = value === 'active';
}

function requestSave(): void {
    if (selectedUser.value?.is_active && !form.is_active) {
        disableConfirmationOpen.value = true;

        return;
    }

    submit();
}

function submit(): void {
    if (!selectedUser.value) return;

    form.patch(route('admin.users.update', { user: selectedUser.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            setEditorOpen(false);
        },
    });
}
</script>

<template>
    <Head title="Felhasználók" />
    <AppLayout :breadcrumbs="[{ title: 'Felhasználók', href: '/admin/users' }]">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold">Felhasználók</h1>
                <p class="text-sm text-muted-foreground">Szerepkörök és hozzáférések kezelése.</p>
            </div>
            <Card
                ><CardContent class="grid gap-3 p-4 md:grid-cols-[1fr_180px_180px_auto] md:items-end"
                    ><div class="grid gap-2">
                        <Label for="user-search">Keresés</Label>
                        <Input id="user-search" v-model="filters.search" placeholder="Név vagy e-mail" @keyup.enter="apply" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="role-filter">Szerepkör</Label
                        ><Select v-model="filters.role"
                            ><SelectTrigger id="role-filter"><SelectValue placeholder="Szerepkör" /></SelectTrigger
                            ><SelectContent
                                ><SelectItem value="all">Minden szerepkör</SelectItem><SelectItem value="admin">Admin</SelectItem
                                ><SelectItem value="user">Felhasználó</SelectItem></SelectContent
                            ></Select
                        >
                    </div>
                    <div class="grid gap-2">
                        <Label for="status-filter">Állapot</Label
                        ><Select v-model="filters.status"
                            ><SelectTrigger id="status-filter"><SelectValue placeholder="Állapot" /></SelectTrigger
                            ><SelectContent
                                ><SelectItem value="all">Minden állapot</SelectItem><SelectItem value="active">Aktív</SelectItem
                                ><SelectItem value="inactive">Letiltott</SelectItem></SelectContent
                            ></Select
                        >
                    </div>
                    <Button @click="apply">Szűrés</Button></CardContent
                ></Card
            ><Card
                ><CardContent class="overflow-x-auto p-0"
                    ><Table
                        ><TableHeader
                            ><TableRow
                                ><TableHead>Felhasználó</TableHead><TableHead>Szerepkör</TableHead><TableHead>Állapot</TableHead
                                ><TableHead class="text-right">Művelet</TableHead></TableRow
                            ></TableHeader
                        ><TableBody
                            ><TableRow v-for="user in users.data" :key="user.id"
                                ><TableCell
                                    ><div class="font-medium">{{ user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ user.email }}</div></TableCell
                                ><TableCell>{{ user.role === 'admin' ? 'Admin' : 'Felhasználó' }}</TableCell
                                ><TableCell>
                                    <div v-if="user.is_active" class="flex items-center gap-2">
                                        <span class="flex items-center gap-2 text-sm font-medium"
                                            ><CircleCheck class="size-4 text-emerald-500" />Aktív</span
                                        >
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <span class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                                            ><Ban class="size-4 text-destructive" />Letiltott</span
                                        >
                                    </div> </TableCell
                                ><TableCell class="text-right"
                                    ><Button size="sm" variant="outline" @click="openEditor(user)"
                                        ><UserRoundCog class="mr-2 size-4" />Megnyitás</Button
                                    ></TableCell
                                ></TableRow
                            ></TableBody
                        ></Table
                    ></CardContent
                ></Card
            >
            <PaginationLinks :links="users.links" />

            <Dialog :open="editorOpen" @update:open="setEditorOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Felhasználó szerkesztése</DialogTitle>
                        <DialogDescription>A fiók alapadatainak és hozzáférésének kezelése.</DialogDescription>
                    </DialogHeader>
                    <form v-if="selectedUser" class="grid gap-5" @submit.prevent="requestSave">
                        <div class="grid gap-2">
                            <Label for="user-name">Név</Label>
                            <Input id="user-name" v-model="form.name" autocomplete="name" required />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="user-email">E-mail-cím</Label>
                            <Input id="user-email" v-model="form.email" type="email" autocomplete="email" required />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="user-role">Szerepkör</Label>
                                <Select v-model="form.role">
                                    <SelectTrigger id="user-role"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="user">Felhasználó</SelectItem>
                                        <SelectItem value="admin">Admin</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.role" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="user-status">Állapot</Label>
                                <Select :model-value="form.is_active ? 'active' : 'inactive'" @update:model-value="setFormStatus">
                                    <SelectTrigger id="user-status"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Aktív</SelectItem>
                                        <SelectItem value="inactive">Letiltott</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.is_active" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="setEditorOpen(false)">Mégse</Button>
                            <Button type="submit" :disabled="form.processing">Módosítások mentése</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <AlertDialog :open="disableConfirmationOpen" @update:open="disableConfirmationOpen = $event">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Biztosan letiltod {{ selectedUser?.name }} fiókját?</AlertDialogTitle>
                        <AlertDialogDescription>
                            A felhasználó nem tud belépni, a meglévő munkamenete pedig megszűnik. A munkaidőadatai megmaradnak.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Mégse</AlertDialogCancel>
                        <AlertDialogAction @click="submit">Fiók letiltása és mentés</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AppLayout>
</template>
