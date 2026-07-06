<script setup>
import { mdiAlertCircle } from '@mdi/js'
import LayoutGuest from '@/layouts/LayoutGuest.vue'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseIcon from '@/components/BaseIcon.vue'
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="emerald">
      <CardBox :class="cardClass">
        <div class="space-y-4 py-6 text-center">
          <BaseIcon :path="mdiAlertCircle" size="64" class="mx-auto text-amber-500" w="w-16" h="h-16" />
          <h1 class="text-2xl font-bold">Page not found</h1>
          <p class="text-gray-500 dark:text-slate-400">
            The page you're looking for doesn't exist.
          </p>
          <BaseButton to="/dashboard" color="success" label="Back to Dashboard" />
        </div>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>
