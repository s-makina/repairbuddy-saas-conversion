async function main() {
  const { createServer } = await import("node:http");
  const { parse } = await import("node:url");
  const next = (await import("next")).default;

  const dev = process.env.NODE_ENV !== "production";
  const hostname = "localhost";
  const port = 3000;
  const app = next({ dev, hostname, port });
  const handle = app.getRequestHandler();

  await app.prepare();

  createServer(async (req, res) => {
    try {
      // Be sure to pass 'true' as the second argument to 'url.parse'.
      // This tells it to parse the query portion of the URL.
      const parsedUrl = parse(req.url, true);
      const { pathname, query } = parsedUrl;

      if (pathname === "/a") {
        await app.render(req, res, "/a", query);
      } else if (pathname === "/b") {
        await app.render(req, res, "/b", query);
      } else {
        await handle(req, res, parsedUrl);
      }
    } catch (err) {
      // eslint-disable-next-line no-console
      console.error("Error occurred handling", req.url, err);
      res.statusCode = 500;
      res.end("internal server error");
    }
  })
    .once("error", (err) => {
      // eslint-disable-next-line no-console
      console.error(err);
      process.exit(1);
    })
    .listen(port, () => {
      // eslint-disable-next-line no-console
      console.log(`Ready on http://${hostname}:${port}`);
    });
}

void main();