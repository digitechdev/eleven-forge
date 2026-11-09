<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ChevronsLeft, PanelLeft } from "@lucide/vue"
import { computed } from "vue"
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip"
import { cn } from "@/lib/utils"
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, openMobile, toggleSidebar } = useSidebar()

const expanded = computed(() => (isMobile.value ? openMobile.value : state.value === "expanded"))
const label = computed(() => (expanded.value ? "Collapse sidebar" : "Expand sidebar"))
const shortcut = typeof navigator !== "undefined" && /Mac|iPhone|iPad/.test(navigator.platform) ? "⌘B" : "Ctrl+B"
</script>

<template>
  <Tooltip>
    <TooltipTrigger as-child>
      <button
        type="button"
        data-sidebar="trigger"
        data-slot="sidebar-trigger"
        :aria-label="label"
        :aria-expanded="expanded"
        :class="cn(
          'group/trigger inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-sidebar-border bg-background text-muted-foreground shadow-xs transition-colors',
          'hover:border-primary/40 hover:bg-primary/5 hover:text-primary',
          'outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
          props.class,
        )"
        @click="toggleSidebar"
      >
        <ChevronsLeft
          v-if="expanded"
          class="size-4 transition-transform duration-200 group-hover/trigger:-translate-x-0.5"
        />
        <PanelLeft
          v-else
          class="size-4"
        />
      </button>
    </TooltipTrigger>
    <TooltipContent side="bottom" class="flex items-center gap-2">
      {{ label }}
      <kbd class="rounded border border-current/20 px-1 font-sans text-[10px] opacity-80">{{ shortcut }}</kbd>
    </TooltipContent>
  </Tooltip>
</template>
