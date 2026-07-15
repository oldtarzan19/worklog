<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';

interface Props {
    class?: string;
}
const page = usePage<SharedData>();
const isAdmin = computed<boolean>(() => {
    return page.props.auth.user.role === 'admin';
});

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none"
            >Worklog
            <p v-if="isAdmin">Admin</p></span
        >
    </div>
</template>
