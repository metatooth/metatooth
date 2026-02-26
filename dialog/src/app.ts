import Editor from './editor';

/// The singleton application
class App {
  editors: Array<Editor>;

  constructor() {
    if (App._instance) {
      throw new Error("Only one App instance allowed.");
    }
    App._instance = this;
    this.editors = new Array<Editor>();
  }

  open(editor: Editor) {
    this.editors.push(editor);
  }
}

export default App;
