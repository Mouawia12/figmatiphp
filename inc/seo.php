<?php

class MetaTagGenerator
{
    private $tags = [];

    public function __construct()
    {
        // Default meta tags
        $this->set('charset', 'UTF-8');
        $this->set('viewport', 'width=device-width, initial-scale=1');
    }

    public function set(string $name, string $content): self
    {
        if(empty($content)) return $this;
        $this->tags['general'][$name] = $content;
        return $this;
    }

    public function setTitle(string $title): self
    {
        if(empty($title)) return $this;
        $this->tags['title'] = $title;
        $this->setOg('title', $title);
        $this->setTwitter('title', $title);
        return $this;
    }

    public function setDescription(string $description): self
    {
        if(empty($description)) return $this;
        $this->set('description', $description);
        $this->setOg('description', $description);
        $this->setTwitter('description', $description);
        return $this;
    }

    public function setKeywords($keywords): self
    {
        if (empty($keywords)) return $this;
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->set('keywords', $keywords);
        return $this;
    }

    public function setCanonical(string $url): self
    {
        if(empty($url)) return $this;
        $this->tags['link']['canonical'] = $url;
        $this->setOg('url', $url);
        return $this;
    }

    public function setOg(string $property, string $content): self
    {
        if(empty($content)) return $this;
        $this->tags['og'][$property] = $content;
        return $this;
    }

    public function setTwitter(string $name, string $content): self
    {
        if(empty($content)) return $this;
        $this->tags['twitter'][$name] = $content;
        return $this;
    }

    public function render(): string
    {
        $html = [];

        if (isset($this->tags['title'])) {
            $html[] = sprintf('<title>%s</title>', htmlspecialchars($this->tags['title']));
        }

        if (isset($this->tags['general'])) {
            foreach ($this->tags['general'] as $name => $content) {
                if ($name === 'charset') {
                    $html[] = sprintf('<meta charset="%s">', htmlspecialchars($content));
                } else {
                    $html[] = sprintf('<meta name="%s" content="%s">', htmlspecialchars($name), htmlspecialchars($content));
                }
            }
        }

        if (isset($this->tags['link']['canonical'])) {
            $html[] = sprintf('<link rel="canonical" href="%s">', htmlspecialchars($this->tags['link']['canonical']));
        }

        if (isset($this->tags['og'])) {
            foreach ($this->tags['og'] as $property => $content) {
                $html[] = sprintf('<meta property="%s" content="%s">', htmlspecialchars($property), htmlspecialchars($content));
            }
        }

        if (isset($this->tags['twitter'])) {
            foreach ($this->tags['twitter'] as $name => $content) {
                $html[] = sprintf('<meta name="%s" content="%s">', htmlspecialchars($name), htmlspecialchars($content));
            }
        }

        return implode(PHP_EOL, $html);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
