<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

class MovieController extends AbstractController
{
    private $em;
    private $movieRepository;
    private $serializer;
    public function __construct(MovieRepository $movieRepository, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->movieRepository = $movieRepository;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /////// Return a JSON response
    #[Route('/test', methods:['GET'], name: 'movies_api')]
    public function hello(): JsonResponse
    {
        return $this->json(['message' => 'Hello from Symfony API!']);
    }

    #[Route('/movie', name: 'app_movie')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MovieController.php',
        ]);
    }

    //////GET ALL MOVIES API - JSON FORMAT - NO ACTORS
    #[Route('/api/movies', methods:['GET'], name: 'movies_only')]
    public function getonlymovies(): JsonResponse
    {
        $movies = $this->movieRepository->findAll();
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('list_movies')
            ->toArray();
        //removed ManyToMany relation error with GROUPS and setting context
        $moviesJson = $this->serializer->serialize(
            $movies,'json', $context);
        return new JsonResponse(
            $moviesJson,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            true
        );
    }

    //////Create new movie api NO template
    #[Route('/api/create', methods:['POST'], name:'movie_create')]
    public function add(Request $request): JsonResponse
    {
        $movie= $this->serializer->deserialize(
            $request->getContent(),
            Movie::class, 'json');
        $this->em->persist($movie);
        $this->em->flush();

        $data =  [
            'title' => $movie->getTitle(),
            'releaseYear' => $movie->getReleaseYear(),
            'description' => $movie->getDescription(),
            'imagePath' => $movie->getImagePath(),
        ];
        return $this->json($data);
    }

    ///////GET SPECIFIC MOVIE API NO TEMPLATE
    #[Route('/api/find/{id}', methods:['GET'], name: 'movies_show')]
    public function showMovie($id): Response
    {
        $movie = $this->movieRepository->find($id);
        if (!$movie) {
            return $this->json('No movie found for id ' . $id, 404);
        }
        $data =  [
            'title' => $movie->getTitle(),
            'releaseYear' => $movie->getReleaseYear(),
            'description' => $movie->getDescription(),
            'imagePath' => $movie->getImagePath(),
        ];
        return $this->json($data);
    }

    /////////Update movie api NO template
    #[Route('/api/update/{id}', name:"movie_update", methods:['PUT'])]
    public function updateMovie($id, Request $request): JsonResponse 
    {
        $movie = $this->movieRepository->findOneBy(['id' => $id]);
        if (!$movie) {
            return $this->json('No movie found for id ' . $id, 404);
        }
        $data= json_decode($request->getContent(),true);

        empty($data['title'])? true : $movie->setTitle($data['title']);
        empty($data['releaseYear'])? true : $movie->setReleaseYear($data['releaseYear']);
        empty($data['description'])? true : $movie->setDescription($data['description']);
        empty($data['imagePath'])? true : $movie->setImagePath($data['imagePath']);
        $this->em->persist($movie);
        $this->em->flush();
            
        $data =  [
            'title' => $movie->getTitle(),
            'releaseYear' => $movie->getReleaseYear(),
            'description' => $movie->getDescription(),
            'imagePath' => $movie->getImagePath(),
        ];
        return $this->json($data);
    }

    //////DELETE A MOVIE API NO TEMPLATE
    #[Route('/api/delete/{id}', methods:['GET','DELETE'], name:'movie_delete')]
    public function deleteMovie($id): JsonResponse
    {
        $movie = $this->movieRepository->find($id);
        if (!$movie) {
            return $this->json('No movie found for id ' . $id, 404);
        }
        $this->em->remove($movie);
        $this->em->flush();

        return $this->json('Deleted a project successfully with id ' . $id);
    }
}
