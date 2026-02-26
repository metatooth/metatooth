const SVG = 'http://www.w3.org/2000/svg';

/// A Viewer handles visualization for an Editor
class Viewer extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  static get observedAttributes() {
    return ['width, height'];
  }

  attributeChangedCallback(name, oldValue, newValue) {
    if (name === 'width' || name === 'height') {
      this.render();
    }
  }

  connectedCallback() {
    this.render();
  }

  render() {
    const width = parseInt(this.getAttribute('width')) || 600;
    const height = parseInt(this.getAttribute('height')) || 600;
    const spacing = parseInt(this.getAttribute('spacing')) || 5;

    this.shadowRoot.innerHTML = '';

    const svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('width', width);
    svg.setAttribute('height', height);

    const frame = document.createElementNS(SVG, 'rect');
    frame.setAttribute('width', width);
    frame.setAttribute('height', height);
    frame.setAttribute('x', 0);
    frame.setAttribute('y', 0);
    frame.setAttribute('style', 'fill:rgb(45, 45, 45);');

    svg.appendChild(frame);

    // CREATE A GRID

    const width_count = width / spacing;
    const x_array = Array.from({length: width_count}, (_, i) => i*spacing)

    x_array.forEach(x => {
      const elem = document.createElementNS(SVG, 'line');
      elem.setAttribute('x1', x);
      elem.setAttribute('y1', 0);
      elem.setAttribute('x2', x);
      elem.setAttribute('y2', height);
      elem.setAttribute('style', 'stroke:white;stroke-width=2;');
      svg.appendChild(elem);
    });

    const height_count = height / spacing;
    const y_array = Array.from({length: height_count}, (_, i) => i*spacing);

    y_array.forEach(y => {
      const elem = document.createElementNS(SVG, 'line');
      elem.setAttribute('x1', 0);
      elem.setAttribute('y1', y);
      elem.setAttribute('x2', width);
      elem.setAttribute('y2', y);
      elem.setAttribute('style', 'stroke:white;stroke-width=2;');
      svg.appendChild(elem);
    });

    this.shadowRoot.appendChild(svg);
  }
}

customElements.define('dialog-viewer', Viewer);

export default Viewer;
