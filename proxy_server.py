import http.server
import urllib.request
import urllib.error
import os
import json
import subprocess
import threading
import time
import sys

os.environ.pop('http_proxy', None)
os.environ.pop('https_proxy', None)
os.environ.pop('HTTP_PROXY', None)
os.environ.pop('HTTPS_PROXY', None)
os.environ.pop('all_proxy', None)
os.environ.pop('ALL_PROXY', None)
os.environ.pop('no_proxy', None)
os.environ.pop('NO_PROXY', None)

H5_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "frontend", "h5")
PHP_PORT = 8081
PHP_HOST = "127.0.0.1"

SKIP_HEADERS = {
    'transfer-encoding',
    'connection',
    'access-control-allow-origin',
    'access-control-allow-methods',
    'access-control-allow-headers',
    'access-control-allow-credentials',
    'access-control-max-age',
}

def start_php_server():
    time.sleep(1)
    proc = subprocess.Popen(
        ["php", "-S", f"{PHP_HOST}:{PHP_PORT}", "-t", "/workspace/public", "/workspace/public/router.php"],
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        text=True,
        bufsize=1,
        universal_newlines=True
    )
    print(f"PHP backend started at http://{PHP_HOST}:{PHP_PORT}")
    for line in proc.stdout:
        print(line, end='')
    proc.wait()

class LocalHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=H5_DIR, **kwargs)

    def log_message(self, format, *args):
        sys.stderr.write("[%s] %s\n" % (self.log_date_time_string(), format % args))

    def do_GET(self):
        if self.path.startswith("/api/"):
            self._forward_to_php("GET")
        elif self.path == "/" or self.path == "/index.html":
            self.path = "/test_home.html"
            super().do_GET()
        elif self.path.startswith("/pages/"):
            file_path = os.path.join(H5_DIR, self.path.lstrip('/'))
            if not os.path.isfile(file_path):
                file_path_html = file_path + ".html"
                if os.path.isfile(file_path_html):
                    self.path = self.path + ".html"
                else:
                    self.send_error(404, 'File not found: %s' % self.path)
            super().do_GET()
        elif self.path.startswith("/#/"):
            self.path = "/index.html"
            super().do_GET()
        else:
            super().do_GET()

    def do_POST(self):
        if self.path.startswith("/api/"):
            self._forward_to_php("POST")
        else:
            super().do_POST()

    def do_OPTIONS(self):
        self.send_response(204)
        self._set_cors_headers()
        self.end_headers()

    def _forward_to_php(self, method):
        try:
            url = f"http://{PHP_HOST}:{PHP_PORT}{self.path}"
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
            proxy_handler = urllib.request.ProxyHandler({})
            opener = urllib.request.build_opener(proxy_handler)
            with opener.open(req, timeout=15) as resp:
                resp_body = resp.read()
                self.send_response(resp.status)
                self._set_cors_headers()
                for key, value in resp.getheaders():
                    if key.lower() not in SKIP_HEADERS:
                        self.send_header(key, value)
                self.end_headers()
                self.wfile.write(resp_body)
        except urllib.error.HTTPError as e:
            resp_body = e.read()
            self.send_response(e.code)
            self._set_cors_headers()
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(resp_body)
        except Exception as e:
            self.send_response(502)
            self._set_cors_headers()
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps({"code": 502, "msg": f"Backend error: {str(e)}"}).encode())

    def _set_cors_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS, PUT, DELETE")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization, token, X-Requested-With, Accept, Origin")
        self.send_header("Access-Control-Max-Age", "86400")

if __name__ == "__main__":
    threading.Thread(target=start_php_server, daemon=True).start()
    port = 8080
    server = http.server.HTTPServer(("0.0.0.0", port), LocalHandler)
    print(f"Local server running at http://localhost:{port}")
    print(f"H5 static files from: {H5_DIR}")
    print(f"API requests forwarded to local PHP backend at http://{PHP_HOST}:{PHP_PORT}")
    server.serve_forever()
