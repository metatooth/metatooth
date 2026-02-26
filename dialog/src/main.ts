import './style.css'
import App from './app'
import Editor from './editor'

const app = new App();
const editor = new Editor();

app.open(editor);

document.querySelector<HTMLDivElement>('#app')!.innerHTML =
  '<dialog-editor></dialog-editor>';
