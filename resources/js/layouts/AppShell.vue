<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b-2 border-border">
            <div class="mx-auto flex max-w-5xl items-center justify-between p-4">
                <Link
                    href="/"
                    class="text-lg font-bold uppercase tracking-widest text-foreground hover:text-primary"
                >
                    Straftat // Scorekeeper
                </Link>
                <button
                    type="button"
                    class="border-2 border-border bg-transparent px-3 py-1 text-xs uppercase tracking-wider text-muted-foreground transition-colors hover:border-destructive hover:text-destructive"
                    @click="logout"
                >
                    Logout
                </button>
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
