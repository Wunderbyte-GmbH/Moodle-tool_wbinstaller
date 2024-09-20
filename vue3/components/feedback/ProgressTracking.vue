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
  <div>
    Progress
    {{ uploadedFileName }}
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useStore } from 'vuex';
import moodleAjax from 'core/ajax'

const store = useStore();
const totalProgress = ref(0);
const taskProgress = ref(0);
let progressInterval = null;

const props = defineProps({
  uploadedFileName: {
    type: String,
    required: true,
  }
});

const startProgressPolling = () => {
  if (!progressInterval) {
    progressInterval = setInterval(getProgress, 1000); // Poll every second
  }
};

const stopProgressPolling = () => {
  if (progressInterval) {
    clearInterval(progressInterval);
    progressInterval = null;
  }
};

onMounted(() => {
  startProgressPolling();
})

onUnmounted(() => {
  stopProgressPolling();
})

const getProgress = async () => {
  try {
    const response = await moodleAjax.call([{
            methodname: 'tool_wbinstaller_get_install_progress',
            args: {
                userid: store.state.user,
                contextid: store.state.contextid,
                filename: props.uploadedFileName,
            }
        }]);

    totalProgress.value = response.progress * 10;
    taskProgress.value = response.subprogress * 10;
  } catch (error) {
    console.error('Error fetching progress:', error);
  }
}

</script>
