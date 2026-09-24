<script setup lang="ts">
import { usePage, router, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import {
    Avatar,
    AvatarFallback,
} from '@/components/ui/avatar';
import {
    Bell,
    Check,
    ChevronsUpDown,
    Laptop,
    LogOut,
    Moon,
    Settings,
    Building2,
    Shield,
    ShieldCheck,
    ShoppingBag,
    Sun,
    TriangleAlert,
    User,
} from 'lucide-vue-next';
import { getInitials } from '@/composables/useInitials';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const page = usePage();
const sekolah = computed(() => page.props.sekolah as { id_sekolah: number; nama_sekolah: string; kode_sekolah: string } | null);
const allSekolah = computed(() => (page.props.all_sekolah as Array<{ id_sekolah: number; nama_sekolah: string; kode_sekolah: string }>) || []);
const auth = computed(() => page.props.auth as { user: { id_user: number; username: string; nama_lengkap: string; role?: { id_role: number; nama_role: string } } } | null);
const user = computed(() => auth.value?.user);
const roleName = computed(() => user.value?.role?.nama_role?.toLowerCase() ?? '');
const isSuperAdmin = computed(() => roleName.value === 'super admin');

const handleLogout = () => {
    router.post('/logout');
};

const switchSekolah = (idSekolah: number) => {
    router.post('/sekolah/switch', { id_sekolah: idSekolah });
};

const toggleQuickTheme = () => {
    if (resolvedAppearance.value === 'dark') {
        updateAppearance('light');
    } else {
        updateAppearance('dark');
    }
};

interface NotifBarang {
    id_barang: number;
    nama: string;
    stok: number;
    stok_minimum: number;
    satuan?: string | null;
}

const notifikasi = computed(() => (page.props.notifikasi as {
    stok_menipis: NotifBarang[];
    total_stok_menipis: number;
} | null) ?? { stok_menipis: [], total_stok_menipis: 0 });

const totalNotif = computed(() => notifikasi.value.total_stok_menipis);
</script>

<template>
    <header
        class="border-sidebar-border/70 flex h-16 shrink-0 items-center gap-4 border-b px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6 bg-white dark:bg-gray-900"
    >
        <!-- Left side: Hamburger + School Dropdown + Breadcrumbs -->
        <div class="flex flex-1 items-center gap-3.5 min-w-0">
            <SidebarTrigger class="-ml-1 flex-shrink-0" />

            <!-- School/Tenant Dropdown -->
            <DropdownMenu v-if="sekolah">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="outline"
                        class="h-9 w-auto px-3 py-1.5 gap-2 text-sm font-medium bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                    >
                        <Building2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span class="truncate max-w-[170px] sm:max-w-[260px]">{{ sekolah.nama_sekolah }}</span>
                        <ChevronsUpDown v-if="isSuperAdmin" class="ml-1 h-3.5 w-3.5 text-muted-foreground shrink-0" />
                    </Button>
                </DropdownMenuTrigger>
                
                <DropdownMenuContent v-if="isSuperAdmin" align="start" class="w-64">
                    <DropdownMenuLabel class="font-semibold text-xs text-muted-foreground">PILIH CABANG SEKOLAH</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    
                    <div class="max-h-56 overflow-y-auto">
                        <DropdownMenuItem
                            v-for="s in allSekolah"
                            :key="s.id_sekolah"
                            class="cursor-pointer flex items-center justify-between py-2 text-sm"
                            :class="s.id_sekolah === sekolah.id_sekolah ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 font-semibold' : ''"
                            @click="switchSekolah(s.id_sekolah)"
                        >
                            <div class="truncate">
                                <div>{{ s.nama_sekolah }}</div>
                                <div class="text-[10px] text-muted-foreground">{{ s.kode_sekolah }}</div>
                            </div>
                            <Check v-if="s.id_sekolah === sekolah.id_sekolah" class="size-4 text-emerald-600 shrink-0 ml-2" />
                        </DropdownMenuItem>
                    </div>

                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <Link href="/sekolah" class="w-full cursor-pointer text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            Kelola Semua Sekolah →
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Breadcrumbs -->
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" class="flex-1 min-w-0" />
            </template>
        </div>

        <!-- Right side: Theme Mode Toggle + Role Badge + User Profile -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Dark / Light Mode Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        title="Ubah Mode Gelap/Terang"
                    >
                        <Sun v-if="resolvedAppearance === 'light'" class="size-4.5 text-amber-500" />
                        <Moon v-else class="size-4.5 text-purple-400" />
                        <span class="sr-only">Tema</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-36">
                    <DropdownMenuLabel class="text-xs text-muted-foreground">PILIH TEMA</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem class="cursor-pointer flex items-center justify-between text-xs" @click="updateAppearance('light')">
                        <span class="flex items-center gap-2"><Sun class="size-3.5 text-amber-500" /> Terang</span>
                        <Check v-if="appearance === 'light'" class="size-3.5 text-emerald-600" />
                    </DropdownMenuItem>
                    <DropdownMenuItem class="cursor-pointer flex items-center justify-between text-xs" @click="updateAppearance('dark')">
                        <span class="flex items-center gap-2"><Moon class="size-3.5 text-purple-400" /> Gelap</span>
                        <Check v-if="appearance === 'dark'" class="size-3.5 text-emerald-600" />
                    </DropdownMenuItem>
                    <DropdownMenuItem class="cursor-pointer flex items-center justify-between text-xs" @click="updateAppearance('system')">
                        <span class="flex items-center gap-2"><Laptop class="size-3.5 text-blue-400" /> Sistem</span>
                        <Check v-if="appearance === 'system'" class="size-3.5 text-emerald-600" />
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Notification Bell -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative h-9 w-9 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                        title="Notifikasi"
                    >
                        <Bell class="size-4.5" />
                        <span
                            v-if="totalNotif > 0"
                            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                        >
                            {{ totalNotif > 99 ? '99+' : totalNotif }}
                        </span>
                        <span class="sr-only">Notifikasi</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80">
                    <DropdownMenuLabel class="font-semibold">Notifikasi</DropdownMenuLabel>
                    <DropdownMenuSeparator />

                    <div class="max-h-80 overflow-y-auto">
                        <div v-if="totalNotif === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                            Tidak ada notifikasi baru. Semua aman.
                        </div>

                        <template v-if="notifikasi.total_stok_menipis > 0">
                            <div class="px-4 pt-2 pb-1 text-xs font-semibold text-muted-foreground uppercase">
                                Stok menipis ({{ notifikasi.total_stok_menipis }})
                            </div>
                            <Link
                                v-for="b in notifikasi.stok_menipis"
                                :key="b.id_barang"
                                href="/produk"
                                class="flex cursor-pointer items-start gap-3 px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-950">
                                    <TriangleAlert class="size-4 text-red-600 dark:text-red-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ b.nama }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Sisa {{ b.stok }} {{ b.satuan || 'pcs' }} (min. {{ b.stok_minimum }})
                                    </p>
                                </div>
                            </Link>
                            <Link
                                v-if="notifikasi.total_stok_menipis > notifikasi.stok_menipis.length"
                                href="/produk"
                                class="block cursor-pointer px-4 py-2 text-center text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline"
                            >
                                Lihat {{ notifikasi.total_stok_menipis - notifikasi.stok_menipis.length }} lainnya →
                            </Link>
                        </template>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Role Badge -->
            <Badge
                v-if="roleName"
                class="hidden sm:inline-flex text-xs capitalize font-medium"
                :class="{
                    'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200': isSuperAdmin,
                    'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200': roleName === 'admin',
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200': roleName === 'kasir',
                }"
            >
                <ShieldCheck v-if="isSuperAdmin" class="mr-1 size-3" />
                <Shield v-else-if="roleName === 'admin'" class="mr-1 size-3" />
                <ShoppingBag v-else class="mr-1 size-3" />
                {{ roleName }}
            </Badge>

            <!-- User Profile Dropdown -->
            <DropdownMenu v-if="user">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        class="focus-within:ring-primary relative h-9 w-auto rounded-full px-2 pr-1 focus-within:ring-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        <Avatar class="h-8 w-8">
                            <AvatarFallback class="rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-semibold text-xs">
                                {{ getInitials(user.nama_lengkap || user.username) }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="hidden md:block ml-2 pr-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ user.nama_lengkap || user.username }}
                        </span>
                        <ChevronsUpDown class="ml-1 h-3.5 w-3.5 text-gray-400" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <DropdownMenuLabel>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ user.nama_lengkap || user.username }}</div>
                        <div class="text-xs text-muted-foreground capitalize">{{ roleName }} - {{ sekolah?.nama_sekolah }}</div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <Link href="/pengaturan" class="flex items-center gap-2 cursor-pointer">
                            <Settings class="h-4 w-4" />
                            Pengaturan
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="handleLogout" class="text-red-600 focus:text-red-600 cursor-pointer">
                        <LogOut class="mr-2 h-4 w-4" />
                        Keluar
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>