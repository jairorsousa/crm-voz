<template>
    <div
        class="overflow-hidden rounded-2xl border border-mono-100 bg-mono-white"
    >
        <div class="overflow-x-auto">
            <table class="min-w-[600px] w-full">
                <thead class="bg-mono-50">
                    <slot name="head" />
                </thead>
                <tbody class="divide-y divide-mono-100">
                    <slot />
                </tbody>
            </table>
        </div>
    </div>
</template>
