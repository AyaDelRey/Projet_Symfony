<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'Oeuvre')]
    private ?User $User = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    private ?Oeuvre $Oeuvre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getOeuvre(): ?Oeuvre
    {
        return $this->Oeuvre;
    }

    public function setOeuvre(?Oeuvre $Oeuvre): static
    {
        $this->Oeuvre = $Oeuvre;

        return $this;
    }
}
