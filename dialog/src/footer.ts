/// The Footer displays state info and messages
class Footer extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    this.render();
  }

  render() {
    this.shadowRoot.innerHTML = '';

    const footer = document.createElement('div');
    footer.setAttribute('class', 'footer');

    footer.innerHTML = '<span>Copyright &copy; 2025 Metatooth</span>';

    this.shadowRoot.appendChild(footer);
  }
}

customElements.define('dialog-footer', Footer);

export default Footer;
