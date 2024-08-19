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
  <div :class="{ 'loading-cursor': isInstalling }" class="container mt-4">
    <div class="form-group">
      <label for="zipFileUpload">
        {{ store.state.strings.vuechooserecipe }}
      </label>
      <input type="file" class="form-control-file" id="zipFileUpload" @change="handleFileUpload" accept=".zip" ref="fileInput"/>
    </div>
    <transition name="fade">
      <div v-if="isInstalling" class="waiting-screen mt-4">
        <div class="spinner"></div>
        <p>
          {{ store.state.strings.vuewaitingtext }}
        </p>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="uploadedFileName && Object.values(feedback).length > 0" class="mt-4">
        <div v-if="feedback.plugins">
          <h3>Plugins of the recipe</h3>
          <div v-if="feedback.plugins.needed">
            <ul class="list-group">
              <li class="list-group-item">
                <b>
                  Mandatory plugins in the ZIP:
                </b>
                <ul>
                  <li v-for="(message, key) in feedback.plugins.needed" :key="key" style="margin-left: 20px; list-style-type: disc;">
                    {{ key }}
                    <PluginFeedback :message/>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
          <div v-if="feedback.plugins.optional">
            <ul class="list-group">
              <li class="list-group-item">
                <b>
                  Optional plugins in the ZIP:
                </b>
                <ul>
                  <li v-for="(message, key) in feedback.plugins.optional" :key="key" style="margin-left: 20px; list-style-type: none;">
                    <input type="checkbox" v-model="checkedOptionalPlugins" :value="key" />
                    {{ key }}
                    <PluginFeedback :message/>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>
        <div v-if="feedback.customfield" class="mt-4">
          <h3>
            {{ store.state.strings.vuecustomfieldzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.customfield.needed" :key="key">
              {{ store.state.strings.vuecategories }}
              {{ key }}
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="courseList.length" class="mt-4">
          <h3>
            {{ store.state.strings.vuecourseszip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="course in courseList" :key="course">{{ course }}</li>
          </ul>
        </div>
        <div v-if="feedback.simulations" class="mt-4">
          <h3>
            {{ store.state.strings.vuesimulationzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.simulations.needed" :key="key">
              {{ key }}
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.questions" class="mt-4">
          <h3>
            {{ store.state.strings.vuequestionszip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.questions.needed" :key="key">
              {{ key }}
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <button
          class="btn btn-primary mt-4"
          @click="installRecipe"
          :disabled="isInstalling"
        >
          {{ store.state.strings.vueinstallbtn }}
        </button>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="!uploadedFileName && Object.values(feedback).length > 0" class="mt-4">
        <FeedbackReport :feedback/>
      </div>
    </transition>
    <!-- <div v-if="isInstalling" class="mt-4">
      <div v-if="isInstalling" class="mt-4">
        <h3>Total Progress:</h3>
        <progress :value="totalProgress" max="100"></progress>
        <h3>Current Task Progress:</h3>
        <progress :value="taskProgress" max="100"></progress>
      </div>
    </div> -->

  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import { useStore } from 'vuex';
import { notify } from "@kyvg/vue3-notification"
import PluginFeedback from '../feedback/PluginFeedback.vue';
import FeedbackReport from '../feedback/FeedbackReport.vue';

// Reactive state for the list of links and courses
const store = useStore();
const courseList = ref([]);
const feedback = ref([]);
const checkedOptionalPlugins = ref([]);
let uploadedFile = null;
let uploadedFileName = ref('');
const fileInput = ref(null);

const isInstalling = ref(false);
const totalProgress = ref(0);
const taskProgress = ref(0);
let progressInterval = null;

const installRecipe = async () => {
  if (uploadedFile) {
    feedback.value = []
    isInstalling.value = true;
    totalProgress.value = 0;
    taskProgress.value = 0;
    startProgressPolling();
    try {
      const base64File = await convertFileToBase64(uploadedFile);
      const selectedPlugins = JSON.stringify(checkedOptionalPlugins.value);
      const response = await store.dispatch('installRecipe',
        {
          uploadedFile: base64File,
          filename: uploadedFileName.value,
          selectedOptionalPlugins: selectedPlugins
        }
      );
      feedback.value = JSON.parse(response.feedback)
      console.log('feedback.value')
      console.log(feedback.value)
      if (response.status == 0) {
        notify({
          title: store.state.strings.success,
          text: store.state.strings.success_description,
          type: 'success'
        });
      } else if (response.status == 1) {
        notify({
          title: store.state.strings.warning,
          text: store.state.strings.warning_description,
          type: 'warn'
        });
      } else if (response.status == 2) {
        notify({
          title: store.state.strings.error,
          text: store.state.strings.error_description,
          type: 'error'
        });
      }
    } catch (error) {
      notify({
        title: store.state.strings.error,
        text: store.state.strings.error_description,
        type: 'error'
      });
    }  finally {
      uploadedFile.value = null
      uploadedFileName.value = ''
      if (fileInput.value) {
        fileInput.value.value = '';
      }
      stopProgressPolling()
      isInstalling.value = false
    }
  }
};

const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
    reader.readAsDataURL(file);
  });
};

// Function to handle file upload
const handleFileUpload = async (event) => {
  feedback.value = []
  isInstalling.value = true;
  uploadedFile = event.target.files[0];
  if (uploadedFile && uploadedFile.name.endsWith('.zip')) {
    uploadedFileName.value = uploadedFile.name;
    try {
      const base64File = await convertFileToBase64(uploadedFile);
      const response = await store.dispatch('checkRecipe',
        {
          uploadedFile: base64File,
          filename: uploadedFileName.value,
        }
      );

      feedback.value = JSON.parse(response.feedback)
    } catch (error) {
      console.error('Error reading ZIP file:', error);
    }
  } else {
    uploadedFileName.value = '';
  }
  isInstalling.value = false;
};

const startProgressPolling = () => {
  //progressInterval = setInterval(getProgress, 100);
};

const stopProgressPolling = () => {
  if (progressInterval) {
    clearInterval(progressInterval);
    progressInterval = null;
  }
};

const getProgress = async () => {
  try {
    const response = await store.dispatch('getInstallProgress', {
      filename: uploadedFileName.value
    });
    totalProgress.value = response.progress * 10
    taskProgress.value = response.subprogress * 10
  } catch (error) {
    console.error('Error fetching progress:', error);
  }
};

onUnmounted(() => {
  stopProgressPolling();
});

</script>

<style scoped>
.waiting-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.waiting-screen p {
  margin-top: 1rem;
  font-size: 1.2rem;
  color: #333;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.list-group{
  margin: 1rem;
}
.loading-cursor {
  cursor: progress;
}
</style>
