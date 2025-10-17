<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/custom/vlogo/templates/page.html.twig */
class __TwigTemplate_42ff9648acbe943a0f3a5caa663629e5 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 45
        yield "<div class=\"layout-container\" id=\"page-wrapper\">
    <div id=\"page\">
        <header role=\"banner\">
            <div id=\"logo\">
                <a href=\"/\"><img src=\"/themes/custom/vlogo/images/logo.png\"></a>
            </div>
            <div id=\"header\">
                ";
        // line 52
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header", [], "any", false, false, true, 52), "html", null, true);
        yield "
            </div>
        </header>
\t\t
        ";
        // line 56
        if (CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "help", [], "any", false, false, true, 56)) {
            // line 57
            yield "        <div id=\"help-wrapper\">
            <div id=\"help\">
                ";
            // line 59
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "help", [], "any", false, false, true, 59), "html", null, true);
            yield "
            </div>
        </div>
        ";
        }
        // line 63
        yield "\t\t
        <navigation>
            <div id=\"primary-menu\">
                ";
        // line 66
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 66), "html", null, true);
        yield "
            </div>
        </navigation>
\t\t
        <main role=\"main\">
            <div class=\"layout-content\" id=\"highlighted\">
                ";
        // line 72
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 72), "html", null, true);
        yield "
            </div>
            <div class=\"layout-content\" id=\"content\">
                <a id=\"main-content\" tabindex=\"-1\"></a>
                ";
        // line 76
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content", [], "any", false, false, true, 76), "html", null, true);
        yield "
            </div>
        </main>
\t\t
        ";
        // line 80
        if (CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer", [], "any", false, false, true, 80)) {
            // line 81
            yield "\t\t<footer role=\"contentinfo\">
\t\t\t";
            // line 82
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer", [], "any", false, false, true, 82), "html", null, true);
            yield "
\t\t</footer>
        ";
        }
        // line 85
        yield "
        ";
        // line 86
        if (CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "secondary_menu", [], "any", false, false, true, 86)) {
            // line 87
            yield "\t\t<div id=\"secondary-menu\">
\t\t\t";
            // line 88
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "secondary_menu", [], "any", false, false, true, 88), "html", null, true);
            yield "
\t\t</div>
        ";
        }
        // line 91
        yield "    </div>
</div>";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/vlogo/templates/page.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  126 => 91,  120 => 88,  117 => 87,  115 => 86,  112 => 85,  106 => 82,  103 => 81,  101 => 80,  94 => 76,  87 => 72,  78 => 66,  73 => 63,  66 => 59,  62 => 57,  60 => 56,  53 => 52,  44 => 45,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/vlogo/templates/page.html.twig", "/var/www/vhosts/vlogo.nl/ca-dev.vlogo.nl/web/themes/custom/vlogo/templates/page.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 56];
        static $filters = ["escape" => 52];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
