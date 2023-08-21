<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PessoaControllerFeatureTest extends TestCase
{
    public function tearDown(): void
    {
        DB::delete('delete from pessoa');
        parent::tearDown();
    }

    public static function providerStore(): \Generator
    {
        yield 'Requisição válida' => [
            'params' => [
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 201,
        ];

        yield 'Requisição com nome faltando' => [
            'params' => [
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 422,
        ];

        yield 'Requisição sintaticamente errada 1 - stack como string' => [
            'params' => [
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => 'PHP, Laravel',
            ],
            'expectedStatus' => 422,
        ];

        yield 'Requisição sintaticamente errada 2 - nome como int' => [
            'params' => [
                'nome' => 1,
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 400,
        ];

        yield 'Requisição sintaticamente errada 3 - apelido como int' => [
            'params' => [
                'nome' => 'João',
                'apelido' => 1,
                'nascimento' => '2000-01-01',
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 400,
        ];

        yield 'Requisição sintaticamente errada 4 - Nascimento como uma palavra' => [
            'params' => [
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => 'um',
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 400,
        ];

        yield 'Requisição sintaticamente errada 5 - Nascimento como int' => [
            'params' => [
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => 1,
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 400,
        ];
    }

    /**
     * @dataProvider providerStore
     */
    public function testStore(array $params, int $expectedStatus): void
    {
        $response = $this->json('POST', '/pessoas', $params)
            ->assertStatus($expectedStatus);
        if ($expectedStatus == 201) {
            unset($params['stack']);
            $this->assertDatabaseHas('pessoa', $params);
            $pessoa = DB::select('select * from pessoa where apelido = :apelido', ['apelido' => $params['apelido']]);
            $expectedLocation = '/pessoa/' . $pessoa[0]->id;
            $response->assertHeader('Location', $expectedLocation);

            return;
        }
    }

    public static function providerShow(): \Generator
    {
        yield 'Pessoa não existe' => [
            'insertBefore' => [],
            'expected' => [
                'status' => 'error',
            ],
            'expectedStatus' => 404,
        ];

        yield 'Busca por pessoa existente' => [
            'insertBefore' => [
                'id' => $id = Str::uuid()->toString(),
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => json_encode(['PHP', 'Laravel']),
            ],
            'expected' => [
                'id' => $id,
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => ['PHP', 'Laravel'],
            ],
            'expectedStatus' => 200,
        ];
    }

    /**
     * @param array $insertBefore
     * @param array $expected
     * @param int $expectedStatus
     * @dataProvider providerShow
     * @return void
     */
    public function testShow(array $insertBefore, array $expected, int $expectedStatus): void
    {
        if ($insertBefore) {
            DB::insert(
                'insert into pessoa(id, nome, apelido, nascimento, stack) values (:id, :nome, :apelido, :nascimento, :stack)',
                $insertBefore
            );
        }

        $url = '/pessoas/' . (optional($expected)['id'] ?: 'invalid-id');
        $res = $this->json('GET', $url);

        $res
            ->assertStatus($expectedStatus)
            ->assertJson($expected);
    }

    public static function providerIndex(): \Generator
    {
        $listaInicial = [
            [
                'id' => Str::uuid()->toString(),
                'nome' => 'João',
                'apelido' => 'joao',
                'nascimento' => '2000-01-01',
                'stack' => json_encode(['PHP', 'Laravel']),
            ],
            [
                'id' => Str::uuid()->toString(),
                'nome' => 'Maria',
                'apelido' => 'maria',
                'nascimento' => '2000-01-01',
                'stack' => json_encode(['PHP', 'Laravel']),
            ],
            [
                'id' => Str::uuid()->toString(),
                'nome' => 'José',
                'apelido' => 'jose',
                'nascimento' => '2000-01-01',
                'stack' => json_encode(['PHP', 'Laravel']),
            ],
        ];

        yield 'Busca sem termo' => [
            'insertBefore' => $listaInicial,
            'term' => null,
            'expected' => [],
            'expectedStatus' => 400,
        ];

        yield 'Busca pelo nome' => [
            'insertBefore' => $listaInicial,
            'term' => 'João',
            'expected' => [
                [
                    'id' => $listaInicial[0]['id'],
                    'nome' => 'João',
                    'apelido' => 'joao',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
            ],
            'expectedStatus' => 200,
        ];

        yield 'Busca pelo apelido' => [
            'insertBefore' => $listaInicial,
            'term' => 'maria',
            'expected' => [
                [
                    'id' => $listaInicial[1]['id'],
                    'nome' => 'Maria',
                    'apelido' => 'maria',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
            ],
            'expectedStatus' => 200,
        ];

        yield 'Busca pelo nascimento' => [
            'insertBefore' => $listaInicial,
            'term' => '2000-01-01',
            'expected' => [
                [
                    'id' => $listaInicial[0]['id'],
                    'nome' => 'João',
                    'apelido' => 'joao',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
                [
                    'id' => $listaInicial[2]['id'],
                    'nome' => 'José',
                    'apelido' => 'jose',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
                [
                    'id' => $listaInicial[1]['id'],
                    'nome' => 'Maria',
                    'apelido' => 'maria',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
            ],
            'expectedStatus' => 200,
        ];

        yield 'Busca pela stack' => [
            'insertBefore' => $listaInicial,
            'term' => 'PHP',
            'expected' => [
                [
                    'id' => $listaInicial[0]['id'],
                    'nome' => 'João',
                    'apelido' => 'joao',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
                [
                    'id' => $listaInicial[2]['id'],
                    'nome' => 'José',
                    'apelido' => 'jose',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
                [
                    'id' => $listaInicial[1]['id'],
                    'nome' => 'Maria',
                    'apelido' => 'maria',
                    'nascimento' => '2000-01-01',
                    'stack' => ['PHP', 'Laravel'],
                ],
            ],
            'expectedStatus' => 200,
        ];
    }

    /**
     * @param string|null $term
     * @param array $expected
     * @param int $expectedStatus
     * @return void
     * @dataProvider providerIndex
     */
    public function testIndex(array $insertBefore, ?string $term, array $expected, int $expectedStatus): void
    {
        if ($insertBefore) {
            array_map(fn ($row) => DB::insert(
                'insert into pessoa(id, nome, apelido, nascimento, stack) values (:id, :nome, :apelido, :nascimento, :stack)',
                $row
            ), $insertBefore);
        }
        $uri = $term ? '/pessoas?t='. $term : '/pessoas';
        $response = $this->json('GET', $uri);
        $response
            ->assertStatus($expectedStatus)
            ->assertJson($expected);
    }

    public function testContagem(): void
    {
        array_map(function ($index) {
            DB::insert(
                'insert into pessoa(id, nome, apelido, nascimento, stack) values (:id, :nome, :apelido, :nascimento, :stack)',
                [
                    'id' => Str::uuid()->toString(),
                    'nome' => fake()->name,
                    'apelido' => fake()->unique()->userName(),
                    'nascimento' => fake()->date,
                    'stack' => json_encode(fake()->randomElements(
                        ['PHP', 'Laravel', null, 'NodeJs', 'Java', 'C#', 'C++', 'Python', 'Ruby', 'Go'],
                        2
                    )),
                ]);
        }, range(1, $total = random_int(1, 10)));

        $response = $this->get('/contagem-pessoas');
        $response->assertStatus(200);
        $this->assertEquals($total, $response->getContent());
    }
}
