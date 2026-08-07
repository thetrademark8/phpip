import { ref } from 'vue'
import axios from 'axios'

// Reads the CSRF token from the meta tag (Laravel's default location).
// bootstrap.js has the global axios setup commented out, so we attach the
// token explicitly here to each upload request.
const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

/**
 * Composable for handling async file uploads with proper progress tracking
 * Fixes the bug where uploading flag was set to false immediately
 */
export function useAsyncUpload(matterId) {
  const uploading = ref(false)
  const uploadProgress = ref(0)
  const uploadError = ref(null)
  const pendingUploads = ref(0)

  /**
   * Upload a single file
   * @param {File} file - File to upload
   * @param {Function} onSuccess - Callback on successful upload
   * @returns {Promise<Object>} Upload response data
   */
  const uploadFile = async (file, onSuccess) => {
    pendingUploads.value++
    uploading.value = true
    uploadError.value = null

    const formData = new FormData()
    formData.append('file', file)

    try {
      const response = await axios.post(
        `/matter/${matterId}/attachments`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
            // Tell Laravel we expect a JSON response. Without this, an
            // unauthenticated/expired session returns a 302 redirect to the
            // login page (which the browser follows), instead of a JSON 401.
            'Accept': 'application/json',
            // Mark the request as AJAX so Laravel's auth middleware returns
            // JSON instead of a full-page redirect.
            'X-Requested-With': 'XMLHttpRequest',
            // CSRF token is required for POST requests. bootstrap.js has the
            // global setup commented out, so send it explicitly.
            'X-CSRF-TOKEN': csrfToken(),
          },
          onUploadProgress: (progressEvent) => {
            if (progressEvent.total) {
              uploadProgress.value = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total
              )
            }
          },
        }
      )

      onSuccess?.(response.data)
      return response.data
    } catch (error) {
      uploadError.value = error.response?.data?.message || 'Upload failed'
      throw error
    } finally {
      pendingUploads.value--
      if (pendingUploads.value === 0) {
        uploading.value = false
        uploadProgress.value = 0
      }
    }
  }

  /**
   * Upload multiple files sequentially
   * @param {FileList|File[]} files - Files to upload
   * @param {Function} onEachSuccess - Callback for each successful upload
   * @returns {Promise<Object[]>} Array of upload results
   */
  const uploadFiles = async (files, onEachSuccess) => {
    const results = []

    for (const file of files) {
      try {
        const result = await uploadFile(file, onEachSuccess)
        results.push({ success: true, data: result, file: file.name })
      } catch (error) {
        results.push({ success: false, error: uploadError.value, file: file.name })
      }
    }

    return results
  }

  /**
   * Reset error state
   */
  const clearError = () => {
    uploadError.value = null
  }

  return {
    uploading,
    uploadProgress,
    uploadError,
    pendingUploads,
    uploadFile,
    uploadFiles,
    clearError,
  }
}
