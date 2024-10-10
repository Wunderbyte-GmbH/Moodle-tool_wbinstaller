<template>
  <div
    v-if="finished"
    class="progress-container"
  >
    <div class="progress-bar-wrapper">
      <div
        class="progress-bar"
        :style="{ width: progressWidth + '%' }">
      </div>
    </div>
    <div class="progress-info">
      {{store.state.strings.vuestepcountersetp}}{{ finished.currentstep }}{{store.state.strings.vuestepcounterof}}{{ finished.maxstep }}
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useStore } from 'vuex'

const store = useStore()

const props = defineProps({
  finished: {
    type: Object,
    required: true,
  }
});

const progressWidth = ref(0);

const updateProgress = () => {
  if (props.finished) {
    progressWidth.value = (props.finished.currentstep / props.finished.maxstep) * 100;
  }
}

// Watch for changes in the finished object to update the progress bar
watch(() => props.finished, updateProgress);

// Set the initial progress when the component mounts
onMounted(() => {
  updateProgress();
});
</script>

<style scoped>
.progress-container {
  display: flex;
  flex-direction: column;
  align-items: flex-end; /* Aligns progress bar to the right */
  padding: 10px;
}

.progress-bar-wrapper {
  width: 100%;
  background-color: #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  height: 20px;
  position: relative;
}

.progress-bar {
  height: 100%;
  background-color: #4caf50; /* Color of the progress bar */
  transition: width 0.5s ease; /* Smooth animation */
  border-radius: 10px 0 0 10px; /* Rounded corners */
}

.progress-info {
  margin-top: 10px;
  font-size: 14px;
  text-align: right;
}
</style>