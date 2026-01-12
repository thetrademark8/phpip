import { ref } from 'vue'
import axios from 'axios'

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
