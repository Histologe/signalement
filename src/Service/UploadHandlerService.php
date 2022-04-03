<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadHandlerService
{
    private ParameterBagInterface $params;
    private SluggerInterface $slugger;
    private $file;

    public function __construct(ParameterBagInterface $parameterBag, SluggerInterface $slugger)
    {
        $this->params = $parameterBag;
        $this->slugger = $slugger;
        $this->file = null;
    }

    public function toTempFolder(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $titre = $originalFilename . '.' . $file->guessExtension();
        // this is needed to safely include the file name as part of the URL
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
//        return ['error'=>'Erreur lors du téléversement.','message'=>'TEST','status'=>500];
        try {
            $file->move(
                $this->params->get('uploads_tmp_dir'),
                $newFilename
            );
        } catch (FileException $e) {
            return ['error'=>'Erreur lors du téléversement.','message'=>$e->getMessage(),'status'=>500];
        }
        if ($newFilename && $newFilename !== '' && $titre && $titre !== '') {
            $this->file = ['file' => $newFilename, 'titre' => $titre];
        }
        return $this;
    }

    public function setKey(string $key)
    {
        $this->file['key'] = $key;
        return $this->file;
    }
}