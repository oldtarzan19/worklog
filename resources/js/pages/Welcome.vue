<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, ChartNoAxesCombined, Clock3, FileSpreadsheet } from 'lucide-vue-next';

const page = usePage<SharedData>();

const features = [
    {
        icon: Clock3,
        title: 'Pontos időrögzítés',
        description: 'Rögzíts több munkaidősávot egy naphoz, a napi és időszaki összesítést pedig a rendszer kiszámítja.',
    },
    {
        icon: CalendarDays,
        title: 'Áttekinthető naptár',
        description: 'A havi naptárban azonnal láthatod a ledolgozott időt, és egy kattintással kezelheted a bejegyzéseidet.',
    },
    {
        icon: ChartNoAxesCombined,
        title: 'Hasznos riportok',
        description: 'A kimutatások, grafikonok és KPI-k segítenek átlátni az egyéni és a csapatszintű munkaidőt.',
    },
    {
        icon: FileSpreadsheet,
        title: 'Excel-export',
        description: 'A szűrt munkaidőadatok részletes és összesített XLSX-fájlban is letölthetők.',
    },
];
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <Head title="Munkaidő-nyilvántartás" />

        <header class="border-b bg-background/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                        <AppLogoIcon class="size-6 fill-current" />
                    </div>
                    <span class="text-lg font-semibold">Worklog</span>
                </div>

                <nav class="flex items-center gap-2" aria-label="Felhasználói műveletek">
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Irányítópult
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-md px-4 py-2 text-sm font-medium hover:bg-muted">Belépés</Link>
                        <Link
                            :href="route('register')"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            Regisztráció
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-[1.15fr_0.85fr] lg:px-8 lg:py-32">
                <div class="flex flex-col justify-center">
                    <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">Munkaidő egyszerűen, egy helyen.</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
                        Rögzítsd a napi munkaidődet, kövesd az összesítéseket, és készíts pontos riportokat egy gyors, magyar nyelvű felületen.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            :href="page.props.auth.user ? route('dashboard') : route('register')"
                            class="rounded-md bg-primary px-5 py-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            {{ page.props.auth.user ? 'Irányítópult megnyitása' : 'Regisztráció indítása' }}
                        </Link>
                        <Link
                            v-if="!page.props.auth.user"
                            :href="route('login')"
                            class="rounded-md border bg-background px-5 py-3 text-sm font-medium transition-colors hover:bg-muted"
                        >
                            Már van fiókom
                        </Link>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <article v-for="feature in features" :key="feature.title" class="rounded-2xl border bg-card p-6 shadow-sm">
                        <div class="mb-4 flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <component :is="feature.icon" class="size-5" />
                        </div>
                        <h2 class="font-semibold">{{ feature.title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ feature.description }}</p>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
