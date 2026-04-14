import type { APIRoute } from "astro";

export const GET: APIRoute = () => {
    return new Response(
        `User-agent: *
Allow: /

Sitemap: https://www.nombredelsitio.com/sitemap-index.xml`,
        {
            headers: {
                "Content-Type": "text/plain",
            },
        }
    );
};