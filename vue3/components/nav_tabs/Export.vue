 <template>
  <div class="container mt-4">
    <transition name="fade" mode="out-in">
      <div class="mt-4">
        <div v-if="courseslist">
          <h3 class="export-title">{{ store.state.strings.exporttitle }}</h3>
          <div class="course-list">
            <div class="course-item" v-for="course in courseslist" :key="course.id">
              <input
                type="checkbox"
                :value="course.id"
                v-model="selectedCourses"
              />
              {{ course.fullname }}
            </div>
          </div>
          <button
            v-if="selectedCourses.length > 0"
            @click="exportCourses"
            :disabled="isExporting"
            class="btn btn-primary mt-3"
          >
          {{ store.state.strings.vueexportselect }}
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useStore } from 'vuex'
const store = useStore()

const props = defineProps({
  courseslist: {
    type: Array,
    default: null,
  }
});

const selectedCourses = ref([]);
const isExporting = ref(false);

const exportCourses = async () => {
  isExporting.value = true;
  await store.dispatch('downloadRecipe', {
    courseids: selectedCourses.value
  })
  isExporting.value = false;

};

</script>

<style scoped>

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.export-title {
  font-weight: bold;
}

.course-list {
  max-height: 400px;
  overflow-y: auto;
  margin-top: 15px;
  border: 1px solid #ccc;
  padding: 10px;
  border-radius: 5px;
}

.course-item {
  display: flex;
  align-items: center;
  padding: 5px;
  border-bottom: 1px solid #eee;
}

.course-item:hover {
  background-color: #f5f5f5;
}

.course-item input {
  margin-right: 10px;
}
</style>
