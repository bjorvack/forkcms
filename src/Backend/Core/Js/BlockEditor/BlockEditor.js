import EditorJS from '@editorjs/editorjs'
import Embed from '@editorjs/embed'
import Header from '@editorjs/header'
import List from '@editorjs/list'
import Paragraph from '@editorjs/paragraph'
import MediaLibraryImage from './Blocks/MediaLibraryImage'

export class BlockEditor {
  constructor () {
    this.initEditors($('textarea.inputBlockEditor'))
    this.loadEditorsInCollections()

    if (this.blocks === undefined) {
      this.blocks = {}
    }

    this.blocks.Header = Header
    this.blocks.Embed = Embed
    this.blocks.List = List
    this.blocks.Paragraph = Paragraph
    this.blocks.MediaLibraryImage = MediaLibraryImage
  }

  initEditors (editors) {
    if (editors.length > 0) {
      editors.each((index, editor) => {
        this.createEditor($(editor))
      })
    }
  }

  createEditor ($element) {
    BlockEditor.fromJson($element, $element.attr('fork-block-editor-config'))
  }

  loadEditorsInCollections () {
    $('[data-addfield="collection"]').on('collection-field-added', (event, formCollectionItem) => {
      this.initEditors($(formCollectionItem).find('textarea.inputBlockEditor'))
    })
  }

  static getClassFromVariableName (string) {
    let scope = window
    let scopeSplit = string.split('.')
    let i

    for (i = 0; i < scopeSplit.length - 1; i++) {
      scope = scope[scopeSplit[i]]

      if (scope === undefined) return
    }

    return scope[scopeSplit[scopeSplit.length - 1]]
  }

  static fromJson ($element, jsonConfig) {
    let config = JSON.parse(jsonConfig)
    for (const name of Object.keys(config)) {
      config[name].class = BlockEditor.getClassFromVariableName(config[name].class)
    }

    BlockEditor.create($element, config)
  }

  static create ($element, tools) {
    $element.hide()
    let editorId = $element.attr('id') + '-block-editor'
    $element.after('<div id="' + editorId + '"></div>')

    let data = {}
    try {
      data = JSON.parse($element.text())
    } catch (e) {
      // ignore the current content since we can't decode it
    }

    const editor = new EditorJS({
      holder: editorId,
      data: data,
      onChange: () => {
        editor.save().then((outputData) => {
          $element.val(JSON.stringify(outputData))
        }).catch((error) => {
          console.debug('Saving failed: ', error)
        })
      },
      tools: tools
    })
  }
}
