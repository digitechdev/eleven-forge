<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';

/** The signed-in user's menu, shown at the top right of the page header. */
const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => page.props.auth.access?.role);
const { getInitials } = useInitials();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger
            class="group/user flex items-center gap-2.5 rounded-sm py-1 pr-2 pl-1 text-left transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 data-[state=open]:bg-accent"
            data-test="header-user-menu"
        >
            <Avatar class="size-8 overflow-hidden rounded-sm">
                <AvatarFallback
                    class="rounded-sm bg-foreground font-mono text-xs font-medium text-background"
                >
                    {{ getInitials(user.name) }}
                </AvatarFallback>
            </Avatar>
            <span class="hidden min-w-0 flex-col leading-tight sm:flex">
                <span class="max-w-40 truncate text-sm font-semibold">{{
                    user.name
                }}</span>
                <span
                    v-if="role"
                    class="max-w-40 truncate label-mono text-primary"
                    >{{ role }}</span
                >
            </span>
            <ChevronDown
                class="hidden size-4 text-muted-foreground group-data-[state=open]/user:rotate-180 motion-safe:transition-transform motion-safe:duration-200 sm:block"
            />
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="min-w-60 rounded-sm"
            align="end"
            :side-offset="8"
        >
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
