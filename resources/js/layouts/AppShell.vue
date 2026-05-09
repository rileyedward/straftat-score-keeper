<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b-2 border-border">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between p-4"
            >
                <Link
                    href="/"
                    class="text-lg font-bold tracking-widest text-foreground uppercase hover:text-primary"
                >
                    Straftat // Scorekeeper
                </Link>
                <button
                    type="button"
                    class="border-2 border-border bg-transparent px-3 py-1 text-xs tracking-wider text-muted-foreground uppercase transition-colors hover:border-foreground hover:text-foreground"
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
