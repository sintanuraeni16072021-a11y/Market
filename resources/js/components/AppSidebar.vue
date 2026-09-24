<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    ShoppingCart,
    ShoppingBag,
    Package,
    Tags,
    FolderOpen,
    Users,
    Truck,
    UserCog,
    FileText,
    Settings,
    Building2,
    History,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();
const auth = computed(() => page.props.auth as { user?: { role?: { nama_role: string } } } | null);
const roleName = computed(() => auth.value?.user?.role?.nama_role?.toLowerCase() ?? '');
const isKasir = computed(() => roleName.value === 'kasir');
const isSuperAdmin = computed(() => roleName.value === 'super admin');

const mainNavItems = computed<NavItem[]>(() => {
    // If Kasir: only show POS, Pelanggan & Piutang
    if (isKasir.value) {
        return [
            {
                title: 'Kasir / Transaksi',
                href: '/kasir',
                icon: ShoppingCart,
            },
            {
                title: 'Pelanggan',
                href: '/pelanggan',
                icon: Users,
            },
        ];
    }

    // Common items for Super Admin & Admin Sekolah
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
    ];

    // Master Sekolah only for Super Admin
    if (isSuperAdmin.value) {
        items.push({
            title: 'Master Sekolah',
            href: '/sekolah',
            icon: Building2,
        });
    }

    items.push(
        {
            title: 'Kasir / Transaksi',
            href: '/kasir',
            icon: ShoppingCart,
        },
        {
            title: 'Pembelian',
            href: '/pembelian',
            icon: ShoppingBag,
        },
        {
            title: 'Produk',
            href: '/produk',
            icon: Package,
        },
        {
            title: 'Kategori',
            href: '/kategori',
            icon: Tags,
        },
        {
            title: 'Kelompok Kategori',
            href: '/kelompok-kategori',
            icon: FolderOpen,
        },
        {
            title: 'Pelanggan',
            href: '/pelanggan',
            icon: Users,
        },
        {
            title: 'Supplier',
            href: '/supplier',
            icon: Truck,
        },
        {
            title: isSuperAdmin.value ? 'Kelola Semua User' : 'Kelola Kasir / User',
            href: '/user',
            icon: UserCog,
        },
        {
            title: isSuperAdmin.value ? 'Laporan Konsolidasi' : 'Laporan Penjualan',
            href: '/laporan',
            icon: FileText,
        },
        {
            title: 'Log Aktivitas',
            href: '/aktivitas',
            icon: History,
        },
        {
            title: 'Pengaturan',
            href: '/pengaturan',
            icon: Settings,
        },
    );

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="isKasir ? '/kasir' : '/dashboard'">
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