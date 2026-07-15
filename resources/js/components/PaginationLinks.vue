<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{ links: PaginationLink[] }>();

function label(link: PaginationLink): string {
    const normalizedLabel = link.label.toLowerCase();

    if (normalizedLabel.includes('previous')) return 'Előző';
    if (normalizedLabel.includes('next')) return 'Következő';

    return link.label.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap justify-center gap-1" aria-label="Lapozás">
        <template v-for="link in links" :key="link.label">
            <Button v-if="link.url" as-child size="sm" :variant="link.active ? 'default' : 'outline'">
                <Link :href="link.url" preserve-scroll :aria-current="link.active ? 'page' : undefined">
                    {{ label(link) }}
                </Link>
            </Button>
            <Button v-else size="sm" variant="outline" disabled>
                {{ label(link) }}
            </Button>
        </template>
    </nav>
</template>
