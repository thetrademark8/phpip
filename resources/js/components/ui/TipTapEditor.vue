<template>
  <div class="tiptap-editor border rounded-lg overflow-hidden">
    <!-- Toolbar -->
    <div v-if="editor && !disabled" class="border-b bg-muted/30 p-1.5 flex flex-wrap gap-0.5">
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('bold') }"
        @click="editor.chain().focus().toggleBold().run()"
        :title="$t('editor.bold')"
      >
        <Bold class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('italic') }"
        @click="editor.chain().focus().toggleItalic().run()"
        :title="$t('editor.italic')"
      >
        <Italic class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('strike') }"
        @click="editor.chain().focus().toggleStrike().run()"
        :title="$t('editor.strikethrough')"
      >
        <Strikethrough class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="mx-1 h-6" />

      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('heading', { level: 1 }) }"
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
        :title="$t('editor.heading1')"
      >
        <Heading1 class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('heading', { level: 2 }) }"
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        :title="$t('editor.heading2')"
      >
        <Heading2 class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('heading', { level: 3 }) }"
        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        :title="$t('editor.heading3')"
      >
        <Heading3 class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="mx-1 h-6" />

      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('bulletList') }"
        @click="editor.chain().focus().toggleBulletList().run()"
        :title="$t('editor.bulletList')"
      >
        <List class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('orderedList') }"
        @click="editor.chain().focus().toggleOrderedList().run()"
        :title="$t('editor.orderedList')"
      >
        <ListOrdered class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        :class="{ 'bg-muted': editor.isActive('blockquote') }"
        @click="editor.chain().focus().toggleBlockquote().run()"
        :title="$t('editor.quote')"
      >
        <Quote class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="mx-1 h-6" />

      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        @click="setLink"
        :class="{ 'bg-muted': editor.isActive('link') }"
        :title="$t('editor.link')"
      >
        <LinkIcon class="h-4 w-4" />
      </Button>
      <Button
        v-if="editor.isActive('link')"
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        @click="editor.chain().focus().unsetLink().run()"
        :title="$t('editor.removeLink')"
      >
        <Unlink class="h-4 w-4" />
      </Button>

      <Separator orientation="vertical" class="mx-1 h-6" />

      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        @click="editor.chain().focus().undo().run()"
        :disabled="!editor.can().undo()"
        :title="$t('editor.undo')"
      >
        <Undo class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 w-8 p-0"
        @click="editor.chain().focus().redo().run()"
        :disabled="!editor.can().redo()"
        :title="$t('editor.redo')"
      >
        <Redo class="h-4 w-4" />
      </Button>
    </div>

    <!-- Editor Content -->
    <EditorContent
      :editor="editor"
      class="prose prose-sm max-w-none p-4 min-h-[200px] focus:outline-none"
      :class="{ 'bg-muted/20': disabled }"
    />
  </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, onBeforeUnmount } from 'vue'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  Bold,
  Italic,
  Strikethrough,
  Heading1,
  Heading2,
  Heading3,
  List,
  ListOrdered,
  Quote,
  Link as LinkIcon,
  Unlink,
  Undo,
  Redo,
} from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  content: props.modelValue,
  editable: !props.disabled,
  extensions: [
    StarterKit,
    Link.configure({
      openOnClick: false,
      HTMLAttributes: {
        class: 'text-primary underline',
      },
    }),
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
  ],
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

// Watch for external content changes
watch(() => props.modelValue, (newValue) => {
  const currentEditor = editor.value
  if (currentEditor && newValue !== currentEditor.getHTML()) {
    currentEditor.commands.setContent(newValue, false)
  }
})

// Watch for disabled changes
watch(() => props.disabled, (newValue) => {
  const currentEditor = editor.value
  if (currentEditor) {
    currentEditor.setEditable(!newValue)
  }
})

// Set link
const setLink = () => {
  const currentEditor = editor.value
  if (!currentEditor) return

  const previousUrl = currentEditor.getAttributes('link').href
  const url = window.prompt('URL', previousUrl)

  if (url === null) {
    return
  }

  if (url === '') {
    currentEditor.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }

  currentEditor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

// Insert text at cursor position (for placeholders)
const insertText = (text) => {
  const currentEditor = editor.value
  if (currentEditor) {
    currentEditor.chain().focus().insertContent(text).run()
  }
}

// Expose methods
defineExpose({
  insertText,
  editor,
})

onBeforeUnmount(() => {
  if (editor.value) {
    editor.value.destroy()
  }
})
</script>

<style>
.tiptap-editor .ProseMirror {
  outline: none;
}

.tiptap-editor .ProseMirror p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.tiptap-editor .ProseMirror ul,
.tiptap-editor .ProseMirror ol {
  padding-left: 1.5rem;
}

.tiptap-editor .ProseMirror blockquote {
  border-left: 3px solid #e5e7eb;
  padding-left: 1rem;
  margin-left: 0;
}
</style>
