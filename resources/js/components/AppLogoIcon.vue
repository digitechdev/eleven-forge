<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        Eleven Forge mark: a light bulb with a gear inside and rays of light, for innovation.
        Shapes set their own fill so callers' `fill-current` doesn't flood the outline.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="none"
            stroke-width="1.8"
            d="M12.5 23.5v-1.4c0-1.5-.8-2.6-1.9-3.8A7.4 7.4 0 1 1 21.4 18.3c-1.1 1.2-1.9 2.3-1.9 3.8v1.4Z"
        />
        <path fill="none" stroke-width="1.8" d="M12.8 26.3h6.4M14 29h4" />
        <path
            fill="none"
            stroke-width="1.6"
            d="M16 1.6v2M5.2 13.5h-2M26.8 13.5h2M8.4 5.9 7 4.5M23.6 5.9 25 4.5"
        />
        <path
            fill="currentColor"
            fill-rule="evenodd"
            stroke="none"
            transform="translate(16 13.5) scale(1.35) translate(-16 -13.5)"
            d="M18.63 12.41L18.71 12.6L19.81 12.65L19.81 14.35L18.71 14.4L18.63 14.59L18.55 14.78L19.29 15.59L18.09 16.79L17.28 16.05L17.09 16.13L16.9 16.21L16.85 17.31L15.15 17.31L15.1 16.21L14.91 16.13L14.72 16.05L13.91 16.79L12.71 15.59L13.45 14.78L13.37 14.59L13.29 14.4L12.19 14.35L12.19 12.65L13.29 12.6L13.37 12.41L13.45 12.22L12.71 11.41L13.91 10.21L14.72 10.95L14.91 10.87L15.1 10.79L15.15 9.69L16.85 9.69L16.9 10.79L17.09 10.87L17.28 10.95L18.09 10.21L19.29 11.41L18.55 12.22ZM17.15 13.5a1.15 1.15 0 1 0-2.3 0a1.15 1.15 0 1 0 2.3 0Z"
        />
    </svg>
</template>
