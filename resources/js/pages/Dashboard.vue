<script setup lang="ts">
import CommercialPanel from '@/components/CommercialPanel.vue';
import type { AdminTeam, AdminUser } from '@/components/CrmAdministration.vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import type { Opportunity } from '@/lib/commercial';
import type { DashboardInvitation } from '@/types';

defineProps<{
    opportunities: Opportunity[];
    pendingInvitations: DashboardInvitation[];
    adminUsers?: AdminUser[];
    adminTeams?: AdminTeam[];
}>();
</script>

<template>
    <div>
        <CommercialPanel
            :records="opportunities"
            :admin-users="adminUsers ?? []"
            :admin-teams="adminTeams ?? []"
        />
        <PendingInvitationsModal
            v-if="pendingInvitations.length"
            :invitations="pendingInvitations"
        />
    </div>
</template>
