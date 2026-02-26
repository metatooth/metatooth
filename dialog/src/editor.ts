import Viewer from './viewer';
import Footer from './footer';

/// The Editor maintains state and handles messages.
class Editor extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    this.shadowRoot.innerHTML = '';

    const container = document.createElement('div');

    const menu = document.createElement('div');
    menu.setAttribute('class', 'menu');

    container.appendChild(menu)

    const dragbar = document.createElement('a');
    dragbar.setAttribute('id', 'dragbar');

    container.appendChild(dragbar);

    const row = document.createElement('div');
    row.setAttribute('class', 'row');

    container.appendChild(row);

    const jsoneditor = document.createElement('div');
    jsoneditor.setAttribute('id', 'jsoneditor');
    jsoneditor.setAttribute('class', 'column');

    row.appendChild(jsoneditor);

    const viewer = document.createElement('dialog-viewer');
    viewer.setAttribute('class', 'column');

    row.appendChild(viewer);

    const footer = document.createElement('dialog-footer');

    container.appendChild(footer);

    this.shadowRoot.appendChild(container);
  }
}

customElements.define('dialog-editor', Editor);

export default Editor;
