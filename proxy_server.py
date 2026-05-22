import http.server
import urllib.request
import urllib.error
import os
import json
from urllib.parse import urlparse, parse_qs

REMOTE_API = "https://wcce.51zhanma.cn"
H5_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "frontend", "h5")

class ProxyHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=H5_DIR, **kwargs)

    def do_GET(self):
        if self.path.startswith("/api/"):
            self._proxy_request("GET")
        else:
            super().do_GET()

    def do_POST(self):
        if self.path.startswith("/api/"):
            self._proxy_request("POST")
        else:
            super().do_POST()

    def do_OPTIONS(self):
        self.send_response(204)
        self._set_cors_headers()
        self.end_headers()

    def _proxy_request(self, method):
        try:
            url = REMOTE_API + self.path
            headers = {}
            for key, value in self.headers.items():
                if key.lower() not in ("host", "origin", "referer"):
                    headers[key] = value

            body = None
            if method == "POST":
                content_length = int(self.headers.get("Content-Length", 0))
                if content_length > 0:
                    body = self.rfile.read(content_length)

            req = urllib.request.Request(url, data=body, headers=headers, method=method)
            with urllib.request.urlopen(req, timeout=15) as resp:
                resp_body = resp.read()
                self.send_response(resp.status)
                for key, value in resp.getheaders():
                    if key.lower() not in ("transfer-encoding", "connection"):
                        self.send_header(key, value)
                self._set_cors_headers()
                self.end_headers()
                self.wfile.write(resp_body)
        except urllib.error.HTTPError as e:
            self.send_response(e.code)
            self._set_cors_headers()
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"code": e.code, "msg": str(e)}).encode())
        except Exception as e:
            self.send_response(502)
            self._set_cors_headers()
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"code": 502, "msg": f"Proxy error: {str(e)}"}).encode())

    def _set_cors_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization, token")

    def end_headers(self):
        if not self._headers_buffer:
            pass
        super().end_headers()

if __name__ == "__main__":
    port = 8080
    server = http.server.HTTPServer(("0.0.0.0", port), ProxyHandler)
    print(f"Proxy server running at http://localhost:{port}")
    print(f"H5 static files from: {H5_DIR}")
    print(f"API requests proxied to: {REMOTE_API}")
    server.serve_forever()
