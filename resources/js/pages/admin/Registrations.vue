<script setup lang="ts">
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { RegistrationRequest } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ requests: { data: RegistrationRequest[] } }>();
const rejecting = ref<RegistrationRequest | null>(null);
function approve(request: RegistrationRequest): void {
    router.post(route('admin.registrations.approve', { registrationRequest: request.id }));
}
function reject(): void {
    if (!rejecting.value) return;
    router.delete(route('admin.registrations.reject', { registrationRequest: rejecting.value.id }), {
        onSuccess: () => {
            rejecting.value = null;
        },
    });
}
</script>

<template>
    <Head title="Regisztrációk" />
    <AppLayout :breadcrumbs="[{ title: 'Regisztrációk', href: '/admin/registrations' }]">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold">Függő regisztrációk</h1>
                <p class="text-sm text-muted-foreground">Hagyd jóvá vagy utasítsd el a hozzáférési kérelmeket.</p>
            </div>
            <Card
                ><CardHeader
                    ><CardTitle
                        >Kérelmek <Badge variant="secondary">{{ requests.data.length }}</Badge></CardTitle
                    ></CardHeader
                ><CardContent class="overflow-x-auto"
                    ><Table
                        ><TableHeader
                            ><TableRow
                                ><TableHead>Név</TableHead><TableHead>E-mail</TableHead><TableHead>Érkezett</TableHead
                                ><TableHead class="text-right">Műveletek</TableHead></TableRow
                            ></TableHeader
                        ><TableBody
                            ><TableRow v-for="request in requests.data" :key="request.id"
                                ><TableCell class="font-medium">{{ request.name }}</TableCell
                                ><TableCell>{{ request.email }}</TableCell
                                ><TableCell>{{ new Date(request.created_at).toLocaleString('hu-HU') }}</TableCell
                                ><TableCell
                                    ><div class="flex justify-end gap-2">
                                        <Button size="sm" @click="approve(request)">Jóváhagyás</Button
                                        ><AlertDialog
                                            :open="rejecting?.id === request.id"
                                            @update:open="(open) => (rejecting = open ? request : null)"
                                            ><AlertDialogTrigger as-child
                                                ><Button size="sm" variant="destructive">Elutasítás</Button></AlertDialogTrigger
                                            ><AlertDialogContent
                                                ><AlertDialogHeader
                                                    ><AlertDialogTitle>Biztosan elutasítod {{ request.name }} kérelmét?</AlertDialogTitle
                                                    ><AlertDialogDescription
                                                        >A függő regisztrációs kérelem végleg törlődik.</AlertDialogDescription
                                                    ></AlertDialogHeader
                                                ><AlertDialogFooter
                                                    ><AlertDialogCancel>Mégse</AlertDialogCancel
                                                    ><AlertDialogAction @click="reject">Kérelem elutasítása</AlertDialogAction></AlertDialogFooter
                                                ></AlertDialogContent
                                            ></AlertDialog
                                        >
                                    </div></TableCell
                                ></TableRow
                            ><TableRow v-if="!requests.data.length"
                                ><TableCell colspan="4" class="h-24 text-center text-muted-foreground">Nincs függő kérelem.</TableCell></TableRow
                            ></TableBody
                        ></Table
                    ></CardContent
                ></Card
            >
        </div>
    </AppLayout>
</template>
