import json, sys
from http.server import BaseHTTPRequestHandler, HTTPServer
B="http://localhost:8099"
class H(BaseHTTPRequestHandler):
    def _send(self, body, code=200):
        if isinstance(body,(dict,)): body=json.dumps(body).encode()
        self.send_response(code); self.send_header("Content-Length",str(len(body))); self.end_headers(); self.wfile.write(body)
        sys.stderr.write("STUB %s %s -> %d\n"%(self.command,self.path,code)); sys.stderr.flush()
    def do_GET(self):
        if self.path.startswith("/plans/"): self._send({"data":{"location":B+"/plan.json"}})
        elif self.path=="/plan.json": self._send({"meshrefs":[{"url":B+"/mesh.stl"}]})
        elif self.path=="/mesh.stl": self._send(b"solid test\nendsolid test\n")
        else: self._send(b"",404)
    def do_POST(self):
        n=int(self.headers.get("Content-Length",0)); self.rfile.read(n)
        self._send({"locator":"asset-123"})
    def do_PUT(self):
        n=int(self.headers.get("Content-Length",0)); self.rfile.read(n)
        self._send(b"")
    def log_message(self,*a): pass
HTTPServer(("127.0.0.1",8099),H).serve_forever()
