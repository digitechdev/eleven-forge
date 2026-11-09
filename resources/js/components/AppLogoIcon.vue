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
    <!-- Eleven Forge mark: an anvil face with a rising spark. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M4 15h17.5c0 3.2 2.6 5.8 5.8 5.8H28v2.4h-6.4l2.4 4.8H8l2.4-4.8H4V15Z"
        />
        <path d="M19 3l1.6 4.4L25 9l-4.4 1.6L19 15l-1.6-4.4L13 9l4.4-1.6Z" />
    </svg>
</template>
