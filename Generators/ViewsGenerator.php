<?php

namespace Modules\Asgardgenerators\Generators;


use Modules\Asgardgenerators\Contracts\Generators\BaseGenerator;
use Modules\Asgardgenerators\Contracts\Generators\GeneratorInterface;

class ViewsGenerator extends BaseGenerator implements GeneratorInterface
{

    /**
     * List of columns taht should not be included in the views
     *
     * @var array
     */
    protected $excluded_columns = [
      'created_at',
      'updated_at',
      'password'
    ];

    /**
     * Execute the generator
     *
     * @return void
     */
    public function execute()
    {
        echo "\nGenerating Views:\n";
        // create the index view per table
        foreach ($this->tables->getInfo() as $table => $columns) {
            // create the base dir for the views
            $base_dir = $this->getFileGenerationPath() . DIRECTORY_SEPARATOR . "{$table}";
            if (!file_exists($base_dir)) {
                mkdir($base_dir);
            }


            foreach ([
                       'index',
                       'show'
                     ] as $item) {
                $this->generate($table, $columns, $item);
            }


        }
    }

    /**
     * Full path to the required template file
     *
     * @return string
     */
    public function getTemplatePath()
    {
        $path = $this->getOption('templatePath', null);

        if (is_null($path)) {
            $path = config('asgard.asgardgenerators.config.views.template',
              "");
        } else {
            $path .= "views";
        }

        return $path;
    }

    /**
     * Create the data used in the template file
     *
     * @return array
     */
    public function getTemplateData()
    {
        return [
        ];
    }

    /**
     * Full path to the output file
     *
     * @return string
     */
    public function getFileGenerationPath()
    {
        $path = $this->module->getPath() . DIRECTORY_SEPARATOR;

        $path .= implode(DIRECTORY_SEPARATOR, [
          "Resources",
          "views",
          "admin"
        ]);

        // ensure the basedir exists
        if (!file_exists($path)) {
            mkdir($path);
        }

        return $path;
    }

    /**
     * Generate the requested view
     *
     * @param string $table
     * @param array  $columns
     * @param string $name
     */
    private function generate($table, $columns = [], $name = "index")
    {

        $file_to_generate = $this->getFileGenerationPath() . DIRECTORY_SEPARATOR . "{$table}" . DIRECTORY_SEPARATOR . "$name.blade.php";

        if ($this->canGenerate(
          $file_to_generate,
          $this->getOption('overwrite', false),
          'view'
        )
        ) {
            $this->generator->make(
              $this->getTemplatePath() . DIRECTORY_SEPARATOR . "$name.txt",
              $this->createData($table, $columns, $name),
              $file_to_generate
            );

            echo "File {$file_to_generate} generated.\n";
        }


    }


    /**
     * @param string $table
     * @return string
     */
    private function createTitleFromTable($table)
    {
        $table = ucwords(str_singular($table));

        return str_replace("_", " ", $table);
    }

    /**
     * Create the default model name from a given table name
     *
     * @param string $table
     * @return string
     */
    private function createDefaultModelNameFromTable($table)
    {
        return camel_case(str_singular($table));
    }

    /**
     * @param string $table
     * @param array  $columns
     * @param string $type
     * @return array
     */
    private function createData($table, $columns = [], $type = 'index')
    {
        // base data
        $model = $this->createDefaultModelNameFromTable($table);

        $data = [
          'BASE_LAYOUT' => config('asgard.asgardgenerators.config.views.base_template_name',
            ""),
          'NAMESPACE'   => $this->getNamespace(),
          'MODEL'       => $model,
          'MODELS'      => camel_case($table),
        ];

        $columns = $this->removeExcluded($columns);

        switch ($type) {
            case 'index':
                $data += [
                  'TABLE_HEADERS'               => $this->createIndexTableHeaderData($table,
                    $columns),
                  'TABLE_CONTENT'               => $this->createIndexTableContentData($table,
                    $columns),
                  'LOWERCASE_MODULE_NAME'       => $this->module->getLowerName(),
                  'PLURAL_LOWERCASE_CLASS_NAME' => str_plural(strtolower($model)),
                  'LOWERCASE_CLASS_NAME'        => strtolower($model),
                ];
                break;
            case 'show':
                $data += [
                  'TITLE' => 'id'
                ];
                break;
        }


        return $data;
    }

    /**
     * Create the table header for the index view
     *
     * @param string $table
     * @param array  $columns
     * @return string
     */
    private function createIndexTableHeaderData($table, $columns)
    {
        $titles = [];

        foreach (array_keys($columns['columns']) as $column) {
            $titles[] = "<th>$column</th>";
        }

        return implode("\n", $titles);
    }

    /**
     * Create the table content for the index view
     *
     * @param string $table
     * @param array  $columns
     * @return string
     */
    private function createIndexTableContentData($table, $columns)
    {
        $line = [];

        // determine the model name
        $model = $this->createDefaultModelNameFromTable($table);

        foreach ($columns['columns'] as $column => $datatype) {
            $line[] = "<td>{{ \${$model}->{$column} }}</td>";
        }

        return implode("\n", $line);
    }

    /**
     * Remove excluded columns from a given list of columns
     *
     * @param array $columns
     * @return array
     */
    private function removeExcluded($columns)
    {
        foreach (array_keys($columns['columns']) as $column) {
            if (in_array($column, $this->excluded_columns)) {
                unset($columns['columns'][$column]);
            }
        }

        return $columns;
    }


}