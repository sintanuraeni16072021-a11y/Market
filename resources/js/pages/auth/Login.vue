<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { request as passwordRequest } from '@/routes/password';
import { AlertTriangle, CheckCircle2, Store } from 'lucide-vue-next';

defineOptions({ layout: AuthSplitLayout });

defineProps<{
    status?: string;
}>();

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const capsLockOn = ref(false);

onMounted(() => {
    document.getElementById('username')?.focus();
});

const checkCapsLock = (event: KeyboardEvent) => {
    capsLockOn.value =
        typeof event.getModifierState === 'function' &&
        event.getModifierState('CapsLock');
};

const focusFirstError = () => {
    if (form.errors.username) {
        document.getElementById('username')?.focus();
    } else if (form.errors.password) {
        document.getElementById('password')?.focus();
    }
};

const submit = () => {
    capsLockOn.value = false;
    form.post('/login', {
        onFinish: () => form.reset('password'),
        onError: focusFirstError,
    });
};
</script>

<template>
    <Head title="Login - KASIRA" />

    <!-- Branding ringkas (mobile saja, panel kiri tampil di desktop) -->
    <div class="mb-6 flex flex-col items-center gap-3 text-center lg:hidden">
        <div class="flex size-14 items-center justify-center rounded-xl bg-emerald-100">
            <Store class="size-8 text-emerald-600" />
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900">KASIRA</h1>
            <p class="text-sm text-muted-foreground">Sistem Kasir Multi Tenant</p>
        </div>
    </div>

    <div class="mb-6 hidden lg:block">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Selamat datang kembali</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Masuk untuk mulai berjualan.
        </p>
    </div>

    <div
        v-if="status"
        class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700"
        role="status"
    >
        <CheckCircle2 class="size-4 shrink-0" />
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-4" novalidate>
        <!-- Username -->
        <div class="grid gap-2">
            <Label for="username" class="text-sm font-medium">Username</Label>
            <Input
                id="username"
                v-model="form.username"
                type="text"
                required
                autocomplete="username"
                placeholder="Masukkan username"
                :aria-invalid="!!form.errors.username"
                class="w-full border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 hover:border-emerald-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
            />
            <InputError :message="form.errors.username" />
        </div>

        <!-- Password -->
        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label for="password" class="text-sm font-medium">Password</Label>
                <TextLink
                    :href="passwordRequest()"
                    class="text-sm text-emerald-600 hover:text-emerald-700"
                    :tabindex="5"
                >
                    Lupa password?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                v-model="form.password"
                required
                autocomplete="current-password"
                placeholder="Masukkan password"
                :aria-invalid="!!form.errors.password"
                class="w-full border-gray-300 bg-gray-50 text-gray-800 placeholder:text-gray-500 hover:border-emerald-500 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                @keyup="checkCapsLock"
            />
            <p
                v-if="capsLockOn"
                class="flex items-center gap-1.5 text-xs font-medium text-amber-600"
                role="alert"
            >
                <AlertTriangle class="size-3.5" />
                Caps Lock menyala — periksa huruf besar/kecil.
            </p>
            <InputError :message="form.errors.password" />
        </div>

        <div class="flex items-center">
            <Label for="remember" class="flex cursor-pointer items-center gap-2">
                <Checkbox id="remember" v-model="form.remember" />
                <span class="text-sm text-gray-700">Ingat saya</span>
            </Label>
        </div>

        <Button
            type="submit"
            class="mt-1 w-full bg-emerald-600 py-2.5 font-medium text-white transition-colors hover:bg-emerald-700"
            :disabled="form.processing"
            data-test="login-button"
        >
            <Spinner v-if="form.processing" class="mr-2 size-4" />
            {{ form.processing ? 'Memproses...' : 'Login' }}
        </Button>

        <p class="text-center text-xs text-gray-500">
            Hubungi admin sekolah jika akun Anda terkunci atau nonaktif.
        </p>
    </form>
</template>
