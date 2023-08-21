<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Dtos\InserePessoaDto;
use App\Http\Requests\InserePessoaRequest;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use TypeError;

class PessoaController extends Controller
{
     public function store(InserePessoaRequest $request): JsonResponse
     {
         try {
             $request->merge([
                 'id' => $id = Str::uuid()->toString(),
                 'stack' => json_encode($request->get('stack')),
             ]);

             $pessoaDto = new InserePessoaDto();
             $pessoaDto->nome = $request->get('nome');
             $pessoaDto->apelido = $request->get('apelido');
             $pessoaDto->nascimento = $request->get('nascimento');
             $pessoaDto->stack = $request->get('stack');

             DB::insert(
                 'insert into pessoa(id, nome, apelido, nascimento, stack) values (:id, :nome, :apelido, :nascimento, :stack)',
                 $request->all()
             );

             return response()
                 ->json($request->all(), 201)->withHeaders([
                     'Location' => '/pessoa/' . $id,
                 ]);
         } catch (TypeError | InvalidFormatException $t) {
             return response()
                 ->json([
                     'status' => 'error',
                 ], 400);
         } catch (Throwable $t) {
             return response()
                 ->json([
                     'status' => 'error',
                 ], 422);
         }
     }

     public function show(string $id): JsonResponse
     {
         try {
             $pessoa = DB::selectOne('select * from pessoa where id = :id', [
                 'id' => $id,
             ]);
             throw_if(is_null($pessoa), new \Exception('Pessoa não encontrada'));

             $pessoa->stack = json_decode($pessoa->stack, true);

             return response()
                 ->json($pessoa);
         } catch (Throwable $t) {
             return response()
                 ->json([
                     'status' => 'error',
                 ], 404);
         }
     }

     public function index(Request $request): JsonResponse
     {
            try {
                $term = $request->get('t');
                if (is_null($term)) {
                    throw new \Exception('Termo de busca não informado');
                }
                $term = '%' . $term . '%';
                $sql = 'select * from pessoa';
                $sql .= ' where nome like :nome';
                $sql .= ' or apelido like :apelido';
                $sql .= ' or nascimento like :nascimento';
                $sql .= ' or stack like :stack';
                $sql .= ' order by nome asc';
                $pessoas = DB::select($sql, [
                    'nome' => $term,
                    'apelido' => $term,
                    'nascimento' => $term,
                    'stack' => $term,
                ]);

                array_map(fn ($pessoa) => $pessoa->stack = json_decode($pessoa->stack, true), $pessoas);

                return response()
                    ->json($pessoas);
            } catch (Throwable $t) {
                return response()
                    ->json([
                        'status' => 'error',
                    ], 400);
            }
     }

     public function contagem(): Response
     {
            try {
                $sql = 'select count(*) as total from pessoa';
                $total = DB::selectOne($sql)->total;

                return response()
                    ->make($total);
            } catch (Throwable $t) {
                return response()
                    ->make('Erro')
                    ->setStatusCode(500);
            }
     }
}
