<script setup lang="ts">
import CommercialPanel from '@/components/CommercialPanel.vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import type { Opportunity } from '@/lib/commercial';
import type { DashboardInvitation } from '@/types';
defineProps<{ opportunities: Opportunity[]; pendingInvitations: DashboardInvitation[] }>();
</script>
<template><div><CommercialPanel :records="opportunities" /><PendingInvitationsModal v-if="pendingInvitations.length" :invitations="pendingInvitations" /></div></template>
