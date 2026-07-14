<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Clock3 } from 'lucide-vue-next';
</script>

<template>
    <AuthLayout title="Jóváhagyásra vár" description="Az adminisztrátor hamarosan elbírálja a kérelmedet.">
        <Head title="Regisztráció függőben" />
        <div class="flex flex-col items-center gap-5 text-center">
            <div class="rounded-full bg-primary/10 p-4"><Clock3 class="size-8 text-primary" /></div>
            <p class="text-sm text-muted-foreground">Az adminisztrátori jóváhagyásig a fiókkal nem lehet belépni.</p>
            <Button as-child class="w-full"><Link :href="route('login')">Vissza a belépéshez</Link></Button>
        </div>
    </AuthLayout>
</template>
