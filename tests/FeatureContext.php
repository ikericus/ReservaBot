<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context
{
    /**
     * Initializes context.
     */
    public function __construct()
    {
        // Constructor vacío por ahora
    }

    /**
     * @Given estoy en la página de inicio
     */
    public function estoyEnLaPaginaDeInicio()
    {
        $this->visitPath('/');
    }

    /**
     * @Given estoy en la página :page
     */
    public function estoyEnLaPagina($page)
    {
        $this->visitPath($page);
    }

    /**
     * @When hago clic en :linkText
     */
    public function hagoClicEn($linkText)
    {
        $this->clickLink($linkText);
    }

    /**
     * @When hago clic en el botón :buttonText
     */
    public function hagoClicEnElBoton($buttonText)
    {
        $this->pressButton($buttonText);
    }

    /**
     * @Then debería ver el título :title
     */
    public function deberiaVerElTitulo($title)
    {
        $this->assertPageContainsText($title);
    }

    /**
     * @Then debería ver :text
     */
    public function deberiaVer($text)
    {
        $this->assertPageContainsText($text);
    }

    /**
     * @Then no debería ver :text
     */
    public function noDeberiaVer($text)
    {
        $this->assertPageNotContainsText($text);
    }

    /**
     * @When completo el campo :field con :value
     */
    public function completoElCampoCon($field, $value)
    {
        $this->fillField($field, $value);
    }

    /**
     * @When completo el formulario con:
     */
    public function completoElFormularioCon(TableNode $table)
    {
        foreach ($table->getRowsHash() as $field => $value) {
            // Reemplazar {timestamp} con timestamp actual
            $value = str_replace('{timestamp}', time(), $value);
            $this->fillField($field, $value);
        }
    }

    /**
     * @When envío el formulario
     */
    public function envioElFormulario()
    {
        $this->getSession()->getPage()->pressButton('submit');
    }

    /**
     * @Then debería estar en la página :page
     */
    public function deberiaEstarEnLaPagina($page)
    {
        $this->assertSession()->addressEquals($this->locatePath($page));
    }

    /**
     * @Then la URL debería contener :text
     */
    public function laUrlDeberiaContener($text)
    {
        $currentUrl = $this->getSession()->getCurrentUrl();
        if (strpos($currentUrl, $text) === false) {
            throw new \Exception("La URL '$currentUrl' no contiene '$text'");
        }
    }

    /**
     * @Then el código de respuesta debe ser :code
     */
    public function elCodigoDeRespuestaDebeSer($code)
    {
        $this->assertSession()->statusCodeEquals($code);
    }

    /**
     * @Then la respuesta debe ser JSON válido
     */
    public function laRespuestaDebeSerJsonValido()
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('La respuesta no es JSON válido: ' . json_last_error_msg());
        }
    }

    /**
     * @Then el JSON debe contener la clave :key
     */
    public function elJsonDebeContenerLaClave($key)
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content, true);
        
        if (!isset($json[$key])) {
            throw new \Exception("La clave '$key' no existe en el JSON");
        }
    }

    /**
     * @Then el JSON debe contener :key con valor :value
     */
    public function elJsonDebeContenerConValor($key, $value)
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content, true);
        
        if (!isset($json[$key])) {
            throw new \Exception("La clave '$key' no existe en el JSON");
        }
        
        if ($json[$key] != $value) {
            throw new \Exception("El valor de '$key' es '{$json[$key]}', se esperaba '$value'");
        }
    }

    /**
     * @Then el JSON :key debe ser verdadero
     */
    public function elJsonDebeSerVerdadero($key)
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content, true);
        
        if (!isset($json[$key]) || $json[$key] !== true) {
            throw new \Exception("La clave '$key' no es verdadera");
        }
    }

    /**
     * @Then debería ver un elemento con id :id
     */
    public function deberiaVerUnElementoConId($id)
    {
        $element = $this->getSession()->getPage()->findById($id);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con id '$id'");
        }
    }

    /**
     * @Then debería ver un elemento con clase :class
     */
    public function deberiaVerUnElementoConClase($class)
    {
        $element = $this->getSession()->getPage()->find('css', '.' . $class);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con clase '$class'");
        }
    }

    /**
     * @Then debería ver un campo con nombre :name
     */
    public function deberiaVerUnCampoConNombre($name)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', "input[name='$name'], select[name='$name'], textarea[name='$name']");
        
        if (null === $element) {
            throw new \Exception("No se encontró campo con name '$name'");
        }
    }

    /**
     * @When espero :seconds segundos
     */
    public function esperoSegundos($seconds)
    {
        sleep((int)$seconds);
    }

    /**
     * @Given estoy autenticado como :username
     */
    public function estoyAutenticadoComo($username)
    {
        // Implementar lógica de autenticación según tu sistema
        // Por ejemplo, visitar login y completar formulario
        $this->visitPath('/login');
        $this->fillField('email', $username);
        $this->fillField('password', 'demo123');
        $this->pressButton('Iniciar Sesión');
    }

    /**
     * @Then debería ver una tabla con :rows filas
     */
    public function deberiaVerUnaTablaConFilas($rows)
    {
        $table = $this->getSession()->getPage()->find('css', 'table');
        
        if (null === $table) {
            throw new \Exception("No se encontró ninguna tabla");
        }
        
        $actualRows = count($table->findAll('css', 'tbody tr'));
        
        if ($actualRows != $rows) {
            throw new \Exception("Se esperaban $rows filas pero se encontraron $actualRows");
        }
    }

    /**
     * @When marco la casilla :checkbox
     */
    public function marcoLaCasilla($checkbox)
    {
        $page = $this->getSession()->getPage();
        
        // Intentar encontrar por id
        $element = $page->findById($checkbox);
        
        // Si no se encuentra por id, intentar por name
        if (null === $element) {
            $element = $page->find('css', "input[name='$checkbox']");
        }
        
        // Si no se encuentra, intentar por label
        if (null === $element) {
            $element = $page->find('css', "input[type='checkbox']");
        }
        
        if (null === $element) {
            throw new \Exception("No se encontró la casilla '$checkbox'");
        }
        
        $element->check();
    }

    /**
     * @When desmarco la casilla :checkbox
     */
    public function desmarcoLaCasilla($checkbox)
    {
        $page = $this->getSession()->getPage();
        
        // Intentar encontrar por id
        $element = $page->findById($checkbox);
        
        // Si no se encuentra por id, intentar por name
        if (null === $element) {
            $element = $page->find('css', "input[name='$checkbox']");
        }
        
        if (null === $element) {
            throw new \Exception("No se encontró la casilla '$checkbox'");
        }
        
        $element->uncheck();
    }

    /**
     * @When selecciono la opción :value del radio button :name
     */
    public function seleccionoLaOpcionDelRadioButton($value, $name)
    {
        $page = $this->getSession()->getPage();
        
        // Buscar el radio button por name y value
        $element = $page->find('css', "input[type='radio'][name='$name'][value='$value']");
        
        if (null === $element) {
            throw new \Exception("No se encontró el radio button con name='$name' y value='$value'");
        }
        
        // Marcar el radio button
        $element->click();
    }

    /**
     * @When hago clic en el elemento con atributo :attribute igual a :value
     */
    public function hagoClicEnElElementoConAtributo($attribute, $value)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', "[{$attribute}='{$value}']");
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con atributo {$attribute}='{$value}'");
        }
        
        $element->click();
    }

    /**
     * @When hago clic en el primer enlace que contiene :text
     */
    public function hagoClicEnElPrimerEnlaceQueContiene($text)
    {
        $page = $this->getSession()->getPage();
        
        // Buscar todos los enlaces
        $links = $page->findAll('css', 'a');
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($href && strpos($href, $text) !== false) {
                $link->click();
                return;
            }
        }
        
        throw new \Exception("No se encontró ningún enlace que contenga '$text'");
    }

    /**
     * @When marco el radio button :name con valor :value
     */
    public function marcoElRadioButtonConValor($name, $value)
    {
        $page = $this->getSession()->getPage();
        
        // Buscar el radio button
        $element = $page->find('css', "input[type='radio'][name='$name'][value='$value']");
        
        if (null === $element) {
            throw new \Exception("No se encontró el radio button '$name' con valor '$value'");
        }
        
        // Intentar seleccionar el radio button directamente
        try {
            $element->selectOption($value);
        } catch (\Exception $e) {
            // Si falla, intentar con click
            try {
                $element->click();
            } catch (\Exception $e2) {
                // Último recurso: buscar por data-plan y hacer clic
                $container = $page->find('css', "[data-plan='$value']");
                if (null !== $container) {
                    $container->click();
                } else {
                    throw new \Exception("No se pudo seleccionar el radio button '$name' con valor '$value'");
                }
            }
        }
    }

    /**
     * @Then el radio button :name con valor :value debe estar seleccionado
     */
    public function elRadioButtonConValorDebeEstarSeleccionado($name, $value)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', "input[type='radio'][name='$name'][value='$value']");
        
        if (null === $element) {
            throw new \Exception("No se encontró el radio button '$name' con valor '$value'");
        }
        
        if (!$element->isChecked()) {
            throw new \Exception("El radio button '$name' con valor '$value' no está seleccionado");
        }
    }

    /**
     * @Then el radio button :name con valor :value no debe estar seleccionado
     */
    public function elRadioButtonConValorNoDebeEstarSeleccionado($name, $value)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', "input[type='radio'][name='$name'][value='$value']");
        
        if (null === $element) {
            throw new \Exception("No se encontró el radio button '$name' con valor '$value'");
        }
        
        if ($element->isChecked()) {
            throw new \Exception("El radio button '$name' con valor '$value' está seleccionado cuando no debería");
        }
    }

    /**
     * @When hago clic en un elemento con clase :class
     */
    public function hagoClicEnUnElementoConClase($class)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', '.' . $class);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con clase '$class'");
        }
        
        $element->click();
    }

    /**
     * @When hago clic en el elemento con id :id
     */
    public function hagoClicEnElElementoConId($id)
    {
        $page = $this->getSession()->getPage();
        $element = $page->findById($id);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con id '$id'");
        }
        
        $element->click();
    }

    /**
     * @Then el campo :field debe tener el valor :value
     */
    public function elCampoDebeTenerElValor($field, $value)
    {
        $page = $this->getSession()->getPage();
        
        // Buscar por id
        $element = $page->findById($field);
        
        // Si no, buscar por name
        if (null === $element) {
            $element = $page->find('css', "input[name='$field']");
        }
        
        if (null === $element) {
            throw new \Exception("No se encontró el campo '$field'");
        }
        
        $actualValue = $element->getValue();
        
        if ($actualValue !== $value) {
            throw new \Exception("El campo '$field' tiene el valor '$actualValue', se esperaba '$value'");
        }
    }

    /**
     * @Then el campo :field debe estar vacío
     */
    public function elCampoDebeEstarVacio($field)
    {
        $this->elCampoDebeTenerElValor($field, '');
    }

    /**
     * @Then la casilla :checkbox debe estar marcada
     */
    public function laCasillaDebeEstarMarcada($checkbox)
    {
        $page = $this->getSession()->getPage();
        
        $element = $page->findById($checkbox);
        
        if (null === $element) {
            $element = $page->find('css', "input[name='$checkbox']");
        }
        
        if (null === $element) {
            throw new \Exception("No se encontró la casilla '$checkbox'");
        }
        
        if (!$element->isChecked()) {
            throw new \Exception("La casilla '$checkbox' no está marcada");
        }
    }

    /**
     * @Then la casilla :checkbox no debe estar marcada
     */
    public function laCasillaNoDebeEstarMarcada($checkbox)
    {
        $page = $this->getSession()->getPage();
        
        $element = $page->findById($checkbox);
        
        if (null === $element) {
            $element = $page->find('css', "input[name='$checkbox']");
        }
        
        if (null === $element) {
            throw new \Exception("No se encontró la casilla '$checkbox'");
        }
        
        if ($element->isChecked()) {
            throw new \Exception("La casilla '$checkbox' está marcada cuando no debería");
        }
    }

    /**
     * @Then el elemento con id :id debe ser visible
     */
    public function elElementoConIdDebeSerVisible($id)
    {
        $element = $this->getSession()->getPage()->findById($id);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con id '$id'");
        }
        
        if (!$element->isVisible()) {
            throw new \Exception("El elemento con id '$id' no es visible");
        }
    }

    /**
     * @Then el elemento con id :id no debe ser visible
     */
    public function elElementoConIdNoDebeSerVisible($id)
    {
        $element = $this->getSession()->getPage()->findById($id);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con id '$id'");
        }
        
        if ($element->isVisible()) {
            throw new \Exception("El elemento con id '$id' es visible cuando no debería");
        }
    }

    /**
     * @Then el elemento con clase :class debe tener el texto :text
     */
    public function elElementoConClaseDebeTenerElTexto($class, $text)
    {
        $element = $this->getSession()->getPage()->find('css', '.' . $class);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con clase '$class'");
        }
        
        $actualText = $element->getText();
        
        if (strpos($actualText, $text) === false) {
            throw new \Exception("El elemento con clase '$class' tiene el texto '$actualText', se esperaba '$text'");
        }
    }

    /**
     * @When selecciono :value del campo :field
     */
    public function seleccionoDelCampo($value, $field)
    {
        $this->selectOption($field, $value);
    }

    /**
     * @Then debería ver un mensaje de éxito
     */
    public function deberiaVerUnMensajeDeExito()
    {
        $page = $this->getSession()->getPage();
        $content = $page->getContent();
        
        // Buscar textos comunes de éxito
        $successTexts = [
            'éxito',
            'exitosa',
            'exitosamente',
            'correctamente',
            'creada',
            'registrado',
            'bienvenido'
        ];
        
        $found = false;
        foreach ($successTexts as $text) {
            if (stripos($content, $text) !== false) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new \Exception("No se encontró ningún mensaje de éxito en la página");
        }
    }

    /**
     * @Then debería ver un mensaje de error
     */
    public function deberiaVerUnMensajeDeError()
    {
        $page = $this->getSession()->getPage();
        $content = $page->getContent();
        
        // Buscar textos comunes de error
        $errorTexts = [
            'error',
            'incorrecto',
            'incorrecta',
            'inválido',
            'inválida',
            'obligatorio',
            'obligatoria',
            'no válido'
        ];
        
        $found = false;
        foreach ($errorTexts as $text) {
            if (stripos($content, $text) !== false) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new \Exception("No se encontró ningún mensaje de error en la página");
        }
    }

    /**
     * @When ejecuto el script :script
     */
    public function ejecutoElScript($script)
    {
        $this->getSession()->executeScript($script);
    }

    /**
     * @Then el atributo :attribute del elemento :id debe ser :value
     */
    public function elAtributoDelElementoDebeSer($attribute, $id, $value)
    {
        $element = $this->getSession()->getPage()->findById($id);
        
        if (null === $element) {
            throw new \Exception("No se encontró elemento con id '$id'");
        }
        
        $actualValue = $element->getAttribute($attribute);
        
        if ($actualValue !== $value) {
            throw new \Exception("El atributo '$attribute' del elemento '$id' es '$actualValue', se esperaba '$value'");
        }
    }
    
    /**
     * @Given no estoy autenticado
     */
    public function noEstoyAutenticado()
    {
        // Limpiar cookies de sesión
        $this->getSession()->reset();
        
        // O si usas un método específico para logout
        // $this->visitPath('/logout');
    }
}