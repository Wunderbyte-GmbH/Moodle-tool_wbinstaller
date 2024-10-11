 <template>
  <div :class="{ 'loading-cursor': isInstalling }" class="container mt-4">
    <div v-if="refreshpage && !finished.status">
      <p>{{ store.state.strings.vuerefreshpage }}</p>
      <a  :href="store.state.wwwroot">
        <button class="btn btn-primary mt-4">
          {{ store.state.strings.vuerefreshpagebtn }}
        </button>
      </a>
    </div>
    <StepCounter :finished/>
    <div class="form-group">
      <label for="zipFileUpload">
        {{ store.state.strings.vuechooserecipe }}
      </label>
      <input
        type="file"
        class="form-control-file"
        id="zipFileUpload"
        @change="handleFileUpload"
        accept=".zip"
        ref="fileInput"
        :disabled="refreshpage"
      />
    </div>
    <transition name="fade">
      <div v-if="isInstalling" class="waiting-screen mt-4">
        <div class="spinner"></div>
        <p>
          {{ store.state.strings.vuewaitingtext }}
        </p>
        <ProgressTracking :uploadedFileName/>
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
                    <h4 style="text-decoration: underline;">
                      {{ key }}
                    </h4>
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
                    <h4 style="text-decoration: underline;">
                      {{ key }}
                    </h4>
                    <PluginFeedback :message/>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>
        <div v-if="feedback.customfields" class="mt-4">
          <h3>
            {{ store.state.strings.vuecustomfieldzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.customfields.needed" :key="key">
              {{ store.state.strings.vuecategories }}
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.courses" class="mt-4">
          <h3>
            {{ store.state.strings.vuecourseszip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.courses.needed" :key="key">
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.localdata" class="mt-4">
          <h3>
            {{ store.state.strings.vuelocaldata }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.localdata.needed" :key="key">
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
              <PluginFeedback :message/>
            </li>
          </ul>
        </div>
        <div v-if="feedback.simulations" class="mt-4">
          <h3>
            {{ store.state.strings.vuesimulationzip }}
          </h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="(message, key) in feedback.simulations.needed" :key="key">
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
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
              <h4 style="text-decoration: underline;">
                {{ key }}
              </h4>
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
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import { useStore } from 'vuex';
import { notify } from "@kyvg/vue3-notification"
import PluginFeedback from '../feedback/PluginFeedback.vue';
import FeedbackReport from '../feedback/FeedbackReport.vue';
import ProgressTracking from '../feedback/ProgressTracking.vue';
import StepCounter from '../feedback/StepCounter.vue'

// Reactive state for the list of links and courses
const store = useStore();
const feedback = ref([]);
const finished = ref(false);
const checkedOptionalPlugins = ref([]);
let uploadedFile = null;
let uploadedFileName = ref('');
const fileInput = ref(null);
let refreshpage = ref(false);

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
      finished.value = JSON.parse(response.finished)
      if (!finished.value.status) {
        refreshpage.value  = true
      }

      if (feedback.value.status == 0) {
        notify({
          title: store.state.strings.success,
          text: store.state.strings.success_description,
          type: 'success'
        });
      } else if (feedback.value.status == 1) {
        notify({
          title: store.state.strings.warning,
          text: store.state.strings.warning_description,
          type: 'warn'
        });
      } else if (feedback.value.status == 2) {
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

      const responseparsed = JSON.parse(response.feedback)
      feedback.value = responseparsed.feedback
      finished.value = responseparsed.finished
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
