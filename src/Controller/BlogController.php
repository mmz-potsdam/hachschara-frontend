<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vnn\WpApiClient\WpClient;

/**
 *
 */
#[Route(path: ['en' => '/news', 'de' => '/kiosk'])]
class BlogController extends DefaultController
{
    #[Route(path: '/', name: 'blog-index')]
    public function blogIndexAction(Request $request, UrlGeneratorInterface $urlGenerator, WpClient $client): Response
    {
        if (is_null($client)) {
            return $this->redirectToRoute('home');
        }

        $posts = [];

        try {
            $posts = $client->posts()->get(null, [
                'per_page' => 15,
            ]);
        }
        catch (\Exception $e) {
            // var_dump($e);
            ; // ignore
        }

        if (empty($posts)) {
            return $this->redirectToRoute('home');
        }

        foreach ($posts as $key => $post) {
            $mediaId = $post['featured_media'];
            if (!empty($mediaId)) {
                $media = $client->media()->get($mediaId);
                $mediaUrl = $media['media_details']['sizes']['onepress-small'];
                $posts[$key]['media_url'] = $mediaUrl;
            }

            // make shortened excerpt clickable
            if (!empty($post['excerpt']['rendered'])) {
                $posts[$key]['excerpt']['rendered'] = preg_replace(
                    '#\[&hellip;\]</p>#',
                    '[<a href="' . htmlspecialchars($urlGenerator->generate('blog', [ 'slug' => $post['slug'] ])) . '">&hellip;</a>]</p>',
                    $post['excerpt']['rendered']
                );
            }
        }

        return $this->render('Blog/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route(path: '/{slug}', name: 'blog')]
    public function blogDetailAction(Request $request, WpClient $client, $slug): Response
    {
        if (is_null($client)) {
            return $this->redirectToRoute('blog-index');
        }

        $posts = [];
        try {
            $posts = $client->posts()->get(null, [
                'slug' => $slug,
            ]);
        }
        catch (\Exception $e) {
            // var_dump($e);
            ; // ignore
        }

        $post = !empty($posts) ? $posts[0] : null;

        if (is_null($post)) {
            return $this->redirectToRoute('blog-index');
        }

        if (!empty($post['featured_media'])) {
            $media = $client->media()->get($post['featured_media']);
            if (!empty($media['media_details']['sizes']['onepress-small'])) {
                $post['media_url'] = $media['media_details']['sizes']['onepress-small'];
            }

            $post['content']['rendered'] = preg_replace_callback(
                "#(<span class='easy-footnote'><a href='(.*?)'\s*title='(.*?)'>)#",
                function ($matches) {
                    return sprintf(
                        '<a data-toggle="tooltip" href="#" title="%s">',
                        $matches[3]
                    );
                },
                $post['content']['rendered']
            );
        }

        return $this->render('Blog/detail.html.twig', [
            'post' => $post,
        ]);
    }
}
