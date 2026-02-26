import clean from "./clean.js";
import etl from "./etl.js";

async function run() {
  if (process.argv.length > 2) {
    const command = process.argv[2];
    if (command.toLowerCase() === "etl") {
      if (process.argv.length !== 4) {
        console.error("Need to provide path to CSV input.");
      } else {
        await etl(process.argv[3]);
      }
    } else if (command.toLowerCase() === "clean") {
      await clean();
    }
  }
}

(async () => {
  run();
})();
