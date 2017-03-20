<?php


namespace SizeID\OAuth2;


class ApiRequest
{

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	private $method;

	private $endpoint;

	private $headers;

	private $body;

	public function __construct($endpoint, $method = self::GET, $headers = [], $body = null)
	{
		$this->endpoint = $endpoint;
		$this->method = $method;
		$this->headers = $headers;
		$this->body = $body;
	}


	public function getMethod()
	{
		return $this->method;
	}

	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}

	public function getEndpoint()
	{
		return $this->endpoint;
	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function setHeaders($headers)
	{
		$this->headers = $headers;
		return $this;
	}

	public function getBody()
	{
		return $this->body;
	}

	public function setBody($body)
	{
		$this->body = $body;
		return $this;
	}

	public function hasBody()
	{
		return $this->body !== NULL;
	}

	public function setHeader($key, $value)
	{
		$this->headers[$key] = $value;
	}


}