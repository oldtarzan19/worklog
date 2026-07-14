<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, LayoutDashboard, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [{ title: 'Munkaidőm', href: '/dashboard', icon: LayoutDashboard }];
    if (page.props.auth.user.role === 'admin') {
        items.push(
            {
                title: `Regisztrációk${page.props.pendingRegistrations ? ` (${page.props.pendingRegistrations})` : ''}`,
                href: '/admin/registrations',
                icon: ClipboardList,
            },
            { title: 'Felhasználók', href: '/admin/users', icon: Users },
            { title: 'Riportok', href: '/admin/reports', icon: ClipboardList },
        );
    }
    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
