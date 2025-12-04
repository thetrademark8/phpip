<script setup>
import { watch, onBeforeUnmount } from "vue";
import { DialogRoot, useForwardPropsEmits } from "reka-ui";

const props = defineProps({
  open: { type: Boolean, required: false },
  defaultOpen: { type: Boolean, required: false },
  modal: { type: Boolean, required: false },
});
const emits = defineEmits(["update:open"]);

const forwarded = useForwardPropsEmits(props, emits);

// Cleanup explicite quand la modale se ferme (fix reka-ui v2.3.2 bug)
const cleanupBody = () => {
  document.body.style.pointerEvents = '';
  document.body.style.overflow = '';
}

watch(() => props.open, (isOpen) => {
  if (!isOpen) {
    // Délai pour laisser l'animation de fermeture se terminer
    setTimeout(cleanupBody, 300);
  }
});

onBeforeUnmount(() => {
  cleanupBody();
});
</script>

<template>
  <DialogRoot data-slot="dialog" v-bind="forwarded">
    <slot />
  </DialogRoot>
</template>
