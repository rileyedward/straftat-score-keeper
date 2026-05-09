<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    passcode: '',
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('passcode'),
    });
};
</script>

<template>
    <Head title="Enter Passcode" />

    <div
        class="flex min-h-screen items-center justify-center bg-background p-6"
    >
        <div class="w-full max-w-sm border-2 border-border bg-card p-8">
            <h1
                class="mb-1 text-3xl font-bold tracking-widest text-foreground uppercase"
            >
                Straftat
            </h1>
            <p
                class="mb-6 text-xs tracking-wider text-muted-foreground uppercase"
            >
                Score Keeper // Authentication
            </p>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label
                        for="passcode"
                        class="mb-2 block text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Passcode
                    </label>
                    <input
                        id="passcode"
                        v-model="form.passcode"
                        type="password"
                        autocomplete="current-password"
                        autofocus
                        class="w-full border-2 border-border bg-background px-3 py-2 font-mono text-foreground focus:border-primary focus:outline-none"
                    />
                    <p
                        v-if="form.errors.passcode"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.passcode }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full border-2 border-primary bg-primary px-4 py-2 font-bold tracking-widest text-primary-foreground uppercase transition-colors hover:bg-background hover:text-primary disabled:opacity-50"
                >
                    Enter
                </button>
            </form>
        </div>
    </div>
</template>
