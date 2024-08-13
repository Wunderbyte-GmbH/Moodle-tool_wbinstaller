<!-- // This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Validate if the string does excist.
 *
 * @package     tool_installer
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ -->

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
            Export Selected
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
